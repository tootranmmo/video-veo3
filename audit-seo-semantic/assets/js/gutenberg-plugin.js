/**
 * Gutenberg Plugin for Real-time SEO Analysis
 */
(function(wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { PanelBody, PanelRow, TextControl, Button, Spinner } = wp.components;
    const { Component, Fragment } = wp.element;
    const { select, subscribe } = wp.data;
    const { decodeEntities } = wp.htmlEntities;

    class SEOAuditSidebar extends Component {
        constructor() {
            super(...arguments);

            this.state = {
                focusKeyword: '',
                analyzing: false,
                analysis: null,
                lastContent: '',
                lastTitle: ''
            };

            this.analyzeContent = this.analyzeContent.bind(this);
            this.updateFocusKeyword = this.updateFocusKeyword.bind(this);
        }

        componentDidMount() {
            // Subscribe to editor changes
            this.unsubscribe = subscribe(() => {
                const editor = select('core/editor');
                const currentContent = editor.getEditedPostContent();
                const currentTitle = editor.getEditedPostAttribute('title');

                // Auto-analyze if content changed significantly
                if (
                    this.state.focusKeyword &&
                    (currentContent !== this.state.lastContent || currentTitle !== this.state.lastTitle)
                ) {
                    clearTimeout(this.analyzeTimeout);
                    this.analyzeTimeout = setTimeout(() => {
                        this.analyzeContent();
                    }, 2000);
                }
            });
        }

        componentWillUnmount() {
            if (this.unsubscribe) {
                this.unsubscribe();
            }
        }

        analyzeContent() {
            const editor = select('core/editor');
            const title = editor.getEditedPostAttribute('title');
            const content = editor.getEditedPostContent();

            this.setState({
                analyzing: true,
                lastContent: content,
                lastTitle: title
            });

            fetch(auditSeoGutenberg.apiUrl + 'analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': auditSeoGutenberg.nonce
                },
                body: JSON.stringify({
                    title: title,
                    content: content,
                    focus_keyword: this.state.focusKeyword
                })
            })
            .then(response => response.json())
            .then(data => {
                this.setState({
                    analyzing: false,
                    analysis: data
                });
            })
            .catch(error => {
                console.error('SEO Analysis error:', error);
                this.setState({ analyzing: false });
            });
        }

        updateFocusKeyword(value) {
            this.setState({ focusKeyword: value });
        }

        getScoreColor(score) {
            if (score >= 80) return '#4caf50';
            if (score >= 60) return '#ff9800';
            return '#f44336';
        }

        getStatusIcon(status) {
            switch(status) {
                case 'success':
                    return '✓';
                case 'warning':
                    return '⚠';
                case 'error':
                    return '✗';
                default:
                    return '○';
            }
        }

        render() {
            const { focusKeyword, analyzing, analysis } = this.state;

            return (
                <Fragment>
                    <PluginSidebarMoreMenuItem target="audit-seo-sidebar">
                        SEO Audit
                    </PluginSidebarMoreMenuItem>

                    <PluginSidebar
                        name="audit-seo-sidebar"
                        title="SEO Audit"
                        icon="search"
                    >
                        <PanelBody title="Focus Keyword" initialOpen={true}>
                            <TextControl
                                label="Enter your focus keyword"
                                value={focusKeyword}
                                onChange={this.updateFocusKeyword}
                                placeholder="e.g., SEO optimization"
                            />
                            <Button
                                isPrimary
                                onClick={this.analyzeContent}
                                disabled={analyzing || !focusKeyword}
                            >
                                {analyzing ? 'Analyzing...' : 'Analyze Content'}
                            </Button>
                        </PanelBody>

                        {analyzing && (
                            <PanelBody>
                                <div style={{ textAlign: 'center', padding: '20px' }}>
                                    <Spinner />
                                    <p>Analyzing your content...</p>
                                </div>
                            </PanelBody>
                        )}

                        {analysis && !analyzing && (
                            <Fragment>
                                <PanelBody title="SEO Score" initialOpen={true}>
                                    <div style={{
                                        textAlign: 'center',
                                        padding: '20px'
                                    }}>
                                        <div style={{
                                            width: '120px',
                                            height: '120px',
                                            border: `10px solid ${this.getScoreColor(analysis.score)}`,
                                            borderRadius: '50%',
                                            display: 'inline-flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            margin: '10px auto'
                                        }}>
                                            <span style={{
                                                fontSize: '36px',
                                                fontWeight: 'bold',
                                                color: this.getScoreColor(analysis.score)
                                            }}>
                                                {analysis.score}
                                            </span>
                                        </div>
                                        <div>
                                            <strong>
                                                {analysis.score >= 80 ? 'Excellent!' :
                                                 analysis.score >= 60 ? 'Good' : 'Needs Work'}
                                            </strong>
                                        </div>
                                    </div>
                                </PanelBody>

                                <PanelBody title="Analysis Results" initialOpen={true}>
                                    {Object.keys(analysis.checks).map(checkName => {
                                        const check = analysis.checks[checkName];
                                        return (
                                            <PanelRow key={checkName} style={{
                                                flexDirection: 'column',
                                                alignItems: 'flex-start',
                                                padding: '10px',
                                                margin: '5px 0',
                                                background: check.status === 'success' ? '#e8f5e9' :
                                                           check.status === 'warning' ? '#fff3e0' : '#ffebee',
                                                borderLeft: `4px solid ${
                                                    check.status === 'success' ? '#4caf50' :
                                                    check.status === 'warning' ? '#ff9800' : '#f44336'
                                                }`,
                                                borderRadius: '4px'
                                            }}>
                                                <strong>
                                                    <span style={{ marginRight: '5px' }}>
                                                        {this.getStatusIcon(check.status)}
                                                    </span>
                                                    {checkName.replace(/_/g, ' ').toUpperCase()}
                                                </strong>
                                                <p style={{ margin: '5px 0 0 0', fontSize: '13px' }}>
                                                    {check.message}
                                                </p>
                                                {check.density !== undefined && (
                                                    <p style={{ margin: '5px 0 0 0', fontSize: '12px', color: '#666' }}>
                                                        Keyword Density: {check.density}%
                                                    </p>
                                                )}
                                            </PanelRow>
                                        );
                                    })}
                                </PanelBody>

                                {analysis.suggestions && analysis.suggestions.length > 0 && (
                                    <PanelBody title="Suggestions" initialOpen={false}>
                                        {analysis.suggestions.map((suggestion, index) => (
                                            <PanelRow key={index} style={{
                                                flexDirection: 'column',
                                                alignItems: 'flex-start',
                                                padding: '10px',
                                                margin: '5px 0',
                                                background: '#f9f9f9',
                                                borderRadius: '4px'
                                            }}>
                                                <span style={{
                                                    display: 'inline-block',
                                                    padding: '2px 8px',
                                                    background: suggestion.priority === 'high' ? '#f44336' : '#ff9800',
                                                    color: 'white',
                                                    borderRadius: '3px',
                                                    fontSize: '11px',
                                                    marginBottom: '5px'
                                                }}>
                                                    {suggestion.priority.toUpperCase()}
                                                </span>
                                                <p style={{ margin: 0, fontSize: '13px' }}>
                                                    {suggestion.message}
                                                </p>
                                            </PanelRow>
                                        ))}
                                    </PanelBody>
                                )}
                            </Fragment>
                        )}

                        <PanelBody title="Help" initialOpen={false}>
                            <p style={{ fontSize: '13px', lineHeight: '1.6' }}>
                                Enter a focus keyword and click "Analyze Content" to get real-time SEO feedback.
                            </p>
                            <p style={{ fontSize: '13px', lineHeight: '1.6' }}>
                                The analysis updates automatically as you edit your content.
                            </p>
                        </PanelBody>
                    </PluginSidebar>
                </Fragment>
            );
        }
    }

    registerPlugin('audit-seo-semantic', {
        render: SEOAuditSidebar,
        icon: 'search'
    });

})(window.wp);

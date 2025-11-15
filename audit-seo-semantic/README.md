# Audit SEO Semantic

A comprehensive WordPress plugin for SEO auditing with Technical Audit, Backlink Audit, and Content Audit features.

## Features

### 1. Technical Audit
Analyze technical SEO aspects of your pages and posts:
- ✅ Meta tags validation (title, description)
- ✅ Heading structure analysis (H1-H6)
- ✅ URL structure optimization
- ✅ Image optimization (alt tags)
- ✅ SSL/HTTPS status check
- ✅ Structured data validation (Schema.org)
- ✅ Robots meta tag analysis
- ✅ SEO score calculation

### 2. Backlink Audit
Manage and analyze your backlinks:
- 🔗 Backlink database management
- 🔗 DoFollow/NoFollow link analysis
- 🔗 Domain Authority tracking
- 🔗 Spam score monitoring
- 🔗 Anchor text analysis
- 🔗 Backlink velocity tracking
- 🔗 Top domains statistics
- 🔗 Toxic/healthy links identification

### 3. Content Audit
Optimize your content for SEO:
- 📝 Content length analysis
- 📝 Readability score (Flesch Reading Ease)
- 📝 Keyword density analysis
- 📝 Focus keyword optimization
- 📝 Paragraph structure check
- 📝 Internal/external links analysis
- 📝 LSI keywords suggestions
- 📝 Content statistics

## Installation

1. Upload the `audit-seo-semantic` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'SEO Audit' in the admin menu

## Usage

### Running a Technical Audit

1. Go to **SEO Audit > Technical Audit**
2. Select a post or page from the dropdown
3. Click "Run Technical Audit"
4. View the results with score and recommendations

### Managing Backlinks

1. Go to **SEO Audit > Backlink Audit**
2. Click "Add New Backlink" to add a backlink
3. Fill in the details:
   - Target URL (your page)
   - Source URL (linking page)
   - Anchor text
   - Link type (DoFollow/NoFollow)
   - Domain Authority
   - Spam Score
4. View statistics and analytics

### Running a Content Audit

1. Go to **SEO Audit > Content Audit**
2. Select a post or page
3. (Optional) Enter a focus keyword
4. Click "Run Content Audit"
5. View detailed content analysis including:
   - Word count
   - Readability score
   - Keyword density
   - Internal/external links

### Settings

Configure plugin settings at **SEO Audit > Settings**:
- Enable/disable audit features
- Set auto-audit on publish
- Configure minimum content length
- Set maximum keyword density threshold

## Database Tables

The plugin creates two custom database tables:

### wp_audit_seo_backlinks
Stores backlink information:
- `id` - Unique identifier
- `url` - Target URL
- `source_url` - Source URL
- `anchor_text` - Anchor text
- `link_type` - DoFollow/NoFollow
- `domain_authority` - Domain authority score
- `page_authority` - Page authority score
- `spam_score` - Spam score
- `status` - Active/Inactive/Disavowed
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### wp_audit_seo_history
Stores audit history:
- `id` - Unique identifier
- `post_id` - WordPress post ID
- `audit_type` - Type of audit (technical/content)
- `score` - Audit score
- `issues` - Serialized issues array
- `recommendations` - Serialized recommendations
- `created_at` - Audit timestamp

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Score Calculation

### Technical Audit Score (100 points)
- Meta tags: 20 points
- Heading structure: 15 points
- URL structure: 10 points
- Images: 15 points
- SSL/HTTPS: 10 points
- Structured data: 15 points
- Robots meta: 15 points

### Content Audit Score (100 points)
- Content length: 15 points
- Readability: 15 points
- Keyword optimization: 30 points
- Paragraph structure: 10 points
- Internal links: 10 points
- External links: 10 points

## Score Interpretation

- **80-100**: Excellent - Your content is well optimized
- **60-79**: Good - Minor improvements needed
- **0-59**: Needs Work - Significant improvements required

## API/Hooks

### Actions

```php
// Run on plugin activation
do_action('audit_seo_semantic_activated');

// Run after audit completion
do_action('audit_seo_audit_completed', $post_id, $audit_type, $results);
```

### Filters

```php
// Modify audit results before saving
$results = apply_filters('audit_seo_results', $results, $post_id, $audit_type);

// Modify minimum content length
$min_length = apply_filters('audit_seo_min_content_length', 300);
```

## Roadmap

Future features planned:
- [ ] Competitor analysis
- [ ] Automated scheduled audits
- [ ] Email notifications
- [ ] Export reports (PDF/CSV)
- [ ] Gutenberg block integration
- [ ] REST API endpoints
- [ ] Multi-language support

## Support

For support, please contact the plugin author or visit the plugin's support forum.

## Changelog

### 1.0.0
- Initial release
- Technical Audit feature
- Backlink Audit feature
- Content Audit feature
- Dashboard with statistics
- Settings page

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed with ❤️ for better SEO

## Screenshots

1. Dashboard with overview statistics
2. Technical Audit results
3. Backlink management interface
4. Content Audit analysis
5. Settings page

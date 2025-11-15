# Audit SEO Semantic - Advanced Features

## 🚀 New Advanced Features

### 4. Scheduled Audits & Automation
Automate your SEO monitoring:
- ⏰ **Scheduled audits** - Run audits automatically (hourly, daily, weekly, monthly)
- 📧 **Email reports** - Get beautiful HTML email reports
- 🔔 **Notifications** - Stay informed about SEO changes
- 📊 **Historical tracking** - Track SEO improvements over time
- 🤖 **Auto-audit on publish** - Automatic audit when publishing content

#### Usage:
```php
// Schedule a weekly technical audit
Audit_SEO_Scheduler::schedule_audit(
    $post_id,
    'weekly',
    array('technical', 'content')
);

// Send email report
Audit_SEO_Scheduler::send_email_report($post_id, $results);
```

### 5. Broken Link Checker
Find and fix broken links automatically:
- 🔍 **Automatic link scanning** - Check all internal and external links
- ⚠️ **Broken link detection** - Identify 404 errors
- 🔄 **Redirect detection** - Find 301/302 redirects
- 📊 **Link statistics** - Track link health over time
- 🛠️ **One-click fix** - Replace or remove broken links
- 📝 **Bulk operations** - Fix multiple links at once

#### Usage:
```php
// Check post for broken links
$results = Audit_SEO_Link_Checker::check_post($post_id);

// Get all broken links across site
$broken_links = Audit_SEO_Link_Checker::get_all_broken_links();

// Fix a broken link
Audit_SEO_Link_Checker::fix_broken_link($post_id, $old_url, $new_url);
```

### 6. Schema Markup Builder
Visual schema markup creation:
- 📋 **Multiple schema types** - Article, Product, Recipe, FAQ, HowTo, LocalBusiness, etc.
- 🎨 **Visual builder** - Easy-to-use interface
- ✅ **Automatic validation** - Ensure valid schema markup
- 🔄 **Auto-generation** - Smart schema based on content type
- 📱 **Rich snippets preview** - See how it appears in search
- 🎯 **Schema types supported**:
  - Article / BlogPosting / NewsArticle
  - Product with reviews
  - Recipe with nutrition info
  - FAQ Page
  - How-To guides
  - Local Business
  - Event, Course, and more

#### Usage:
```php
// Generate Article schema
$schema = Audit_SEO_Schema_Builder::generate_article_schema($post_id);

// Save product schema
Audit_SEO_Schema_Builder::save_schema($post_id, 'Product', $product_data);

// Auto-generate schema based on content
$schema = Audit_SEO_Schema_Builder::auto_generate_schema($post_id);
```

### 7. Advanced Export & Reports
Professional SEO reports:
- 📄 **CSV export** - Spreadsheet-friendly format
- 📊 **JSON export** - API and developer-friendly
- 🖨️ **Printable HTML** - Beautiful print-ready reports
- 📈 **Visual charts** - Charts and graphs included
- 🎨 **Custom branding** - White-label ready
- 📧 **Email delivery** - Send reports to clients

#### Usage:
```php
// Export to CSV
Audit_SEO_Export::export_to_csv($data, 'seo-report');

// Export to JSON
Audit_SEO_Export::export_to_json($data, 'seo-report');

// Generate printable HTML report
$html = Audit_SEO_Export::generate_html_report($data);
```

### 8. Chart.js Analytics Integration
Visual data representation:
- 📊 **Score history charts** - Track SEO improvement over time
- 🥧 **Backlink distribution** - Pie charts for link types
- 📈 **Content metrics trends** - Line graphs for content performance
- 🎯 **Radar charts** - SEO areas performance comparison
- 📉 **Backlink velocity** - Track link acquisition rate
- 🔵 **Domain Authority distribution** - Visualize link quality

#### Charts Available:
- Score History Chart (Line)
- Backlink Distribution (Doughnut)
- Content Trend Chart (Bar)
- SEO Areas Performance (Radar)
- Backlink Velocity (Line)
- DA Distribution (Bar)
- Keyword Density (Horizontal Bar)
- Comparison Charts (Multi-bar)

### 9. Gutenberg Real-time SEO
Edit with live SEO feedback:
- ⚡ **Real-time analysis** - SEO score updates as you type
- 🎯 **Focus keyword tracking** - Live keyword optimization
- 📝 **Inline suggestions** - Immediate improvement tips
- 🎨 **Editor sidebar** - Dedicated SEO panel
- 📊 **Live metrics** - Word count, readability, keyword density
- ✅ **Instant validation** - Title, meta, headings check
- 🚦 **Color-coded feedback** - Green, yellow, red indicators

#### Features:
- Real-time content scoring
- Focus keyword density tracking
- Title length validation
- Heading structure analysis
- Readability assessment
- Link count tracking
- Auto-suggestions as you type

### 10. AI Content Optimizer
Intelligent content recommendations:
- 🤖 **Smart LSI keywords** - AI-generated related keywords
- 📝 **Content outline generator** - Auto-create content structure
- 🎯 **Keyword density optimization** - Perfect keyword balance
- 📊 **Semantic analysis** - Understand content meaning
- 💡 **Title suggestions** - AI-powered title ideas
- ✍️ **Content gap analysis** - Find missing topics
- 🎨 **Structure recommendations** - Optimal heading structure
- 📈 **Readability improvements** - Sentence and paragraph suggestions

#### Usage:
```php
// Generate LSI keywords
$lsi_keywords = Audit_SEO_AI_Optimizer::generate_lsi_keywords('SEO optimization', $content);

// Get optimization suggestions
$suggestions = Audit_SEO_AI_Optimizer::optimize_content($post_id, 'focus keyword');

// Generate content outline
$outline = Audit_SEO_AI_Optimizer::generate_content_outline('SEO tips');
```

### 11. Google PageSpeed Insights Integration
Core Web Vitals monitoring:
- ⚡ **Performance scores** - Real PageSpeed Insights data
- 📊 **Core Web Vitals** - LCP, CLS, TBT tracking
- 🎯 **Mobile & Desktop** - Test both strategies
- 📈 **Historical tracking** - Monitor performance over time
- 💡 **Optimization opportunities** - Actionable suggestions
- 🔍 **Detailed diagnostics** - Deep performance analysis
- 📸 **Screenshot capture** - Visual page representation

#### Metrics Tracked:
- **Performance Score** (0-100)
- **Accessibility Score**
- **Best Practices Score**
- **SEO Score**
- **Largest Contentful Paint (LCP)**
- **Cumulative Layout Shift (CLS)**
- **Total Blocking Time (TBT)**
- **First Contentful Paint (FCP)**
- **Speed Index**
- **Time to Interactive (TTI)**

#### Usage:
```php
// Run PageSpeed test
$results = Audit_SEO_PageSpeed::run_test($url, 'mobile');

// Get historical data
$history = Audit_SEO_PageSpeed::get_historical_data($url, 'mobile', 30);

// Assess Core Web Vitals
$assessment = Audit_SEO_PageSpeed::assess_core_web_vitals($results);
```

## 📦 Installation

All advanced features are included in the plugin. Just activate and use!

## 🎯 Quick Start with Advanced Features

### 1. Set Up Scheduled Audits
```php
// In your theme or plugin
add_action('init', function() {
    Audit_SEO_Scheduler::schedule_audit(
        get_the_ID(),
        'weekly',
        array('technical', 'content', 'broken_links')
    );
});
```

### 2. Enable Gutenberg Real-time SEO
Just install the plugin - Gutenberg integration is automatic!
Look for the "SEO Audit" sidebar in the block editor.

### 3. Generate Schema Markup
```php
// Automatically add schema to your posts
add_action('wp_head', function() {
    if (is_singular()) {
        $schema = Audit_SEO_Schema_Builder::auto_generate_schema(get_the_ID());
        Audit_SEO_Schema_Builder::output_schema(get_the_ID());
    }
});
```

### 4. Run PageSpeed Tests
```php
// Test your homepage
$results = Audit_SEO_PageSpeed::run_test(home_url(), 'mobile');
```

## 🔧 Configuration

### PageSpeed Insights API Key
Get a free API key from Google Cloud Console:
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project
3. Enable PageSpeed Insights API
4. Create credentials (API Key)
5. Add key in plugin settings

### Email Reports
Configure in Settings:
- Report email address
- Send frequency
- Include chart attachments
- Custom email template

## 📊 Database Schema

### Additional Tables Created:

#### wp_audit_seo_pagespeed
```sql
CREATE TABLE wp_audit_seo_pagespeed (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    url varchar(255) NOT NULL,
    strategy varchar(20) NOT NULL DEFAULT 'mobile',
    performance_score int(3) DEFAULT 0,
    accessibility_score int(3) DEFAULT 0,
    best_practices_score int(3) DEFAULT 0,
    seo_score int(3) DEFAULT 0,
    lcp_value decimal(10,2) DEFAULT 0,
    cls_value decimal(10,4) DEFAULT 0,
    tbt_value decimal(10,2) DEFAULT 0,
    full_results longtext,
    tested_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## 🎨 Customization

### Custom Schema Types
```php
// Add custom schema type
add_filter('audit_seo_schema_types', function($types) {
    $types['CustomType'] = 'My Custom Type';
    return $types;
});
```

### Custom Email Template
```php
// Modify email template
add_filter('audit_seo_email_template', function($html, $data) {
    // Customize $html
    return $html;
}, 10, 2);
```

## 🚀 Performance

All advanced features are optimized for performance:
- Asynchronous operations
- Efficient caching
- Minimal database queries
- CDN-hosted libraries (Chart.js)
- Background processing for heavy tasks

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## 🤝 Support

For advanced feature support:
- Check documentation at `/docs`
- Visit support forum
- Create GitHub issue

## 📜 License

GPL v2 or later

## 🌟 Credits

- Chart.js for visualizations
- Google PageSpeed Insights API
- WordPress Block Editor API

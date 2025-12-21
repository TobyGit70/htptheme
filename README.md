# Happy Turtle FSE Theme

WordPress Full Site Editing theme for Happy Turtle Processing.

## Features

- Full Site Editing (FSE) support
- Splash screen with ATOM animation
- Age gate integration
- Responsive hero section with video
- Custom patterns and templates
- GitHub auto-updates

## Installation

1. Download the theme zip from GitHub releases
2. Go to **Appearance → Themes → Add New → Upload Theme**
3. Upload the zip and activate

## Automatic Updates

This theme checks GitHub for updates automatically. When a new release is available, you'll see the update notification in **Appearance → Themes**.

### Creating a New Release

1. Update the version number in `style.css`:
   ```css
   Version: 1.1.0
   ```

2. Commit and push changes:
   ```bash
   git add -A
   git commit -m "Version 1.1.0 - Description of changes"
   git push
   ```

3. Create a GitHub release:
   - Go to https://github.com/TobyGit70/htptheme/releases/new
   - Tag: `v1.1.0` (must match version in style.css)
   - Title: `v1.1.0`
   - Add release notes
   - Click **Publish release**

4. WordPress will detect the update within 6 hours (or check manually in **Dashboard → Updates**)

## Theme Structure

```
happyturtle-fse/
├── assets/
│   ├── css/
│   ├── images/
│   ├── js/
│   ├── splash.js
│   ├── style.css
│   └── patterns.css
├── parts/
│   ├── header.html
│   └── footer.html
├── patterns/
│   ├── hero-section.php
│   └── services-section.php
├── templates/
│   ├── index.html
│   ├── front-page.html
│   └── page-*.html
├── functions.php
├── style.css
└── theme.json
```

## License

GPL v2 or later

## Author

Happy Turtle Processing, Inc.

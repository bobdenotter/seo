Bolt SEO extension
==================

The Bolt SEO extension is an extension to help you improve the search engine indexing of
your Bolt website. It does this by:

 - Allowing you to specify the SEO title and meta-description for your pages.
 - Adding meta tags to your HTML to facilitate indexing of your pages using meta tags and
   OG tags.
 - Override the canonical, if you really want to.
 - Set the `<meta name="robots">`-tag.

Setup
-----
To use this extension, you should add a field to your contenttypes, and add the tags to
the header of your HMTL templates.

In your contenttypes, you should add a single `seo` field. The extenion will use this to
store the data for the different fields that show in the backend when editing a record.
Simply add it to your fields like this;

```
 pages:
    name: Pages
    singular_name: Page
    fields:
        [..]
        seo:
            type: seo
            group: "SEO settings"
```

You can assign the fields their own tab, using the `group: 'SEO settings'`, to keep them
organised in the backend.

After you've done this, it will look like this in the Bolt backend:

![screen shot 2015-04-07 at 09 40 29](https://cloud.githubusercontent.com/assets/1833361/7018887/2a4a4f64-dd0a-11e4-9486-697f8e058d6b.png)

To add the SEO title and Meta tags to your HTML, edit your templates (the 'master' or
'header') to have the following:

```HTML
    <title>{{ seo.title() }}</title>
    {{ seo.metatags() }}
```

When you've done this, all pages that make use of these templates will automatically have
the correct `<title>` tag and the meta- and OG-tags.

This is a new extension, so the functionality is still pretty bare bones. What's there
works well, but there is probably a lot of functionality to add, to improve search engine
indexing. If you'd like to contribute, or have a good idea, feel free to open an issue on
the tracker at the [SEO Extension repository](https://github.com/bobdenotter/seo/issues) on Github.

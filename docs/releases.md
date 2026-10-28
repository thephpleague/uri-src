---
layout: default
title: Releases and Changelog
redirect_from:
    - /changelog/
    - /upgrading/
    - /upgrading/changelog/
---

# Releases

These are the release notes from monorepo `Uri-src`. We’ve tried to cover all changes, if we’ve missed anything, feel free to create an issue, or send a pull request.

{% for release in site.github.releases %}

## [{{ release.name }}]({{ release.html_url }})

**Date released: {{ release.published_at | date: "%Y-%m-%d" }}**

{{ release.body | replace:'```':'~~~' | markdownify }}
{% endfor %}

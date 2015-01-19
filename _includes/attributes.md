{% assign filename    = page.path | replace: '_drafts/','' | replace: '_posts/','' | replace: '.md','' | replace: '.textile','' | replace: '.markdown','' %}
{% assign asset_code  = "/assets/articles/codes/PAGE_ID/" | replace: 'PAGE_ID',filename %}
{% assign asset_image = "/assets/articles/images/PAGE_ID/" | replace: 'PAGE_ID',filename %}

---
layout: post
title:  "Rackspace Cloud Monitoring Dashboard"
description: 
date:   2015-01-02 14:31:25
categories: 
tags:  
image: /assets/article_images/2015-01-02-cloud-monitoring-dashboard/banner-circles-darker.jpg
---

As a group that heavily dogfoods our own products, the MyCloud team are regular users of the APIs fronted by the [Rackspace Cloud Control Panel][mycloud].  Of these technologies, the Cloud Control Panel itself is run on top of a series of Cloud Servers that are watched by Cloud Monitoring.  We tie these alarms into a variety of services to notify our DevOps team, from [IRC bots](https://hubot.github.com/) to PagerDuty alerts.

![Cloud Monitoring alarms displayed inside of the Cloud Control Panel.](/assets/article_images/2015-01-02-cloud-monitoring-dashboard/mycloud-monitors.jpg)

While both the [Cloud Control Panel][mycloud] and [Cloud Intelligence][cloud_intelligence] offer good overviews of the servers and monitors, our team was in need of a system that could achieve the following:

 * Provide a quick overview of the status of all of the alerts. 
 * Span multiple accounts.
 * Serve as a dashboard.

![Monitoring alarms as displayed inside of Cloud Intelligence](/assets/article_images/2015-01-02-cloud-monitoring-dashboard/cloud-intelligence-monitoring.jpg)

To fulfill these needs, we spent a portion of a team hackweek in early November designing a Cloud Monitoring dashboard.  This dashboard, pictured below, provides an at-a-glance system status for all of our servers.

![The Cloud Monitoring dashboard.  <a href="/assets/article_images/2015-01-02-cloud-monitoring-dashboard/cloud-monitoring-dashboard.png">Click for a larger image.</a>](/assets/article_images/2015-01-02-cloud-monitoring-dashboard/cloud-monitoring-dashboard.png)

<img src="/assets/article_images/2015-01-02-cloud-monitoring-dashboard/dashboard-logo-512.png" style="float: left; width: 100px; margin-right: 30px;"> Each circle represents a different server on the account.  The numbers in the center represent the number of **OK** alarms and the total number of alarms on the server, respectively.  The segmented green ring provides a visual representation of the number of alarms in a **WARNING** or a **CRITICAL** state.

On the backend, the dashboard is a Python Flask app with a dependency of Redis for caching and some basic persistent storage.  The Flask app serves both the frontend HTML application and a JSON API (API details can be found on the project's [GitHub page][github]).  Once a user has been added to the dashboard through the settings page, the API authenticates with the user's token and performs an [Overview call][cm_overview_call] to the Cloud Monitoring API.  The results of this call are stripped down to the required components and stored in Redis.

On the front end, the dashboard uses Jinja2 for templating, Twitter Bootstrap for layout, and jQuery for dynamic content.  The dashboard will refresh its contents from the API every 20 seconds.

![The MyCloud team currently uses this dashboard to monitor their web properties multiple accounts.](/assets/article_images/2015-01-02-cloud-monitoring-dashboard/office-dashboards.jpg)

The dashboard was specifically designed to run on Heroku's free tier with [Redis Cloud][rediscloud] as the database provider.  To try out the Cloud Monitoring dashboard with your own servers, click here:
<center><a href="https://heroku.com/deploy?template=https://github.com/amussey/cloud-monitoring-dashboard">
    <img src="https://www.herokucdn.com/deploy/button.png">
</a></center>

To view the source, visit the project's GitHub page: [https://github.com/amussey/cloud-monitoring-dashboard][github]


[github]: https://github.com/amussey/cloud-monitoring-dashboard
[mycloud]: https://mycloud.rackspace.com
[cloud_intelligence]: https://intelligence.rackspace.com
[rediscloud]: https://redislabs.com
[cm_overview_call]: http://docs.rackspace.com/cm/api/v1.0/cm-devguide/content/service-views.html#GET_getviewOvw_views_overview_service-views

=== GHActivity ===
Contributors: jeherve
Tags: GitHub, Tracking, Productivity,
Stable tag: 1.4.2
Requires at least: 4.4.2
Tested up to: 4.7

Monitor all your activity on GitHub, and posts reports about it on your WordPress site.

== Description ==

Monitor all your activity on GitHub, and posts reports about it on your WordPress site.

If you find bugs, you can report them [here](http://wordpress.org/support/plugin/ghactivity), or submit a pull request [on GitHub](https://github.com/jeherve/ghactivity/).

You can also use the `[jeherve_ghactivity]` shortcode to display stats on the frontend.

== Installation ==

1. Install the GHActivity plugin via the WordPress.org plugin repository, or via your dashboard
2. Activate the plugin
3. Go to Settings > GHActivity Activity Settings in your dashboard and configure the plugin.
4. Enjoy! :)

== Frequently Asked Questions ==

== Changelog ==

= 1.4.2 =
* Make sure the chart data displayed in wp-admin is correct. It should not be polluted by data from the shortcode.

= 1.4.1 =
* Fix Fatal Error because of missing file.

= 1.4.0 =
* Start tracking most popular issues in repos.
* Track multiple people within a team, and get stats for each one of them.

= 1.3.1 =
* Add [Selective Refresh](https://make.wordpress.org/core/2016/03/22/implementing-selective-refresh-support-for-widgets/) support to the widget.
* Add number of commits to the reports.
* Add new event type for comments on commits.

= 1.3 =
* Add new option for those who do not want to store info from private repos.
* Add New GitHub Activity Widget, and new `[ghactivity]` shortcode, to display reports on the frontend.

= 1.2.1 =
* Make sure the chart data isn't added anywhere but where necessary

= 1.2 =
* Change authentication method. Use personal tokens instead, so the plugin can capture activity in private repos. **You will need to configure the plugin again.**
* Introduce Charts to display the main report in the settings screen.
* Refactor to prepare for other ways to display the reports.

= 1.1 =
* Add basic reporting in the plugin settings screen.

= 1.0.0 =
* Initial release.

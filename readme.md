# Ghactivity

**Track your GitHub activity and your team's**

## How the plugin works

### Administration

Plugin's administration happens under Settings > GitHub Activity Settings.

To get started, you'll need to define at least one GitHub username and an access token you can get from GitHub, and that allows to query all activity, including from private repos. Those options are then stored in an array in `ghactivity`.

That admin page is managed via the `admin.ghactivity.php` file.

### Logs

You probably also noticed the GitHub Events & GitHub Issues menus. Those are added, alongside custom taxonomies, via `cpt.ghactivity.php`.

Those CPTs and custom taxonomies get filled every hour, based on calls made to the GitHub API using the info you provided in the admin settings.

In addition to monitoring activity for a specific  GitHub username, you can also monitor all events for a specific repo. You can activate that for each repo appearing under GitHub Events > Repos.

You can view the calls made to the GitHub API and all functions used to log all the data in `core.ghactivity.php`.

## Working with the data

`core.ghactivity.php` also includes some functions that query the data to count events. There are 3 main areas where you can call those functions and create reports:

- `reports.ghactivity.php`, used alongside `charts.ghactivity.php` to display a chart
- `rest.ghactivity.php`, where I started adding endpoints to query the data.
- The different shortcodes under the `/shortcodes` directory.

This is less than ideal and a bit confusing. Instead of doing things in different places like that, I think everything should go via `rest.ghactivity.php`. I thought we could then build each shortcode to query the API for data, and display it using shared components.

## What still needs to be done

Check [the issues here](https://github.com/jeherve/ghactivity/issues) to get some ideas.

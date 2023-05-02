# Meeting Calendar

This Meeting Calendar provides a way of scheduling recurring meetings, and displaying a calendar or timetable.

## Getting Started

1.	Make sure you have [`git`](https://git-scm.com/), [`node`](https://nodejs.org/), and [`npm`](https://www.npmjs.com/get-npm) installed.
2.	Clone this repository into your `\plugins` folder.
3.	Execute `npm install` from the root directory of the repository to install the dependencies.
4.	Execute `npm start` for development mode (`npm run build` for a production build).
5.  Activate the `Meeting Calendar` plugin in your WordPress plugin directory
6.  Create some meetings
7.  While editing your page/post, add in the `Meeting Calendar` block and publish!

## Development environment

You can (optionally) use [`wp-env`](https://developer.wordpress.org/block-editor/packages/packages-env/) to set up a local environment.

1. Install the node dependencies `npm install`
2. Start the wp-env environment with `npm run wp-env start`
3. Visit your new local environment at `http://localhost:8888`

### Running PHPUnit Tests

1. Install the composer dependencies `composer install`
2. If you haven't yet, install the node dependencies `npm install`
3. Start the wp-env environment with `npm run wp-env start`
4. Run the tests with `npm run test:unit-php`

### Enable ICS links
Calendars and individual events can be accessed through `.ics` links, for example http://localhost:8888/meetings.ics. For these links to work, the `permalink_structure` option must be set in the `wp_options` database table, and the appropriate rule must be present in `.htaccess`.

You can set both with the following command:

```shell
# The --hard flag updates .htaccess rules as well as rules in the database.
# For more info see:
# https://developer.wordpress.org/cli/commands/rewrite/structure/

wp-env run cli "wp rewrite structure --hard '/%postname%'"
```

## License

Meeting Calendar is licensed under [GNU General Public License v2 (or later)](./LICENSE.md).

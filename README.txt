=== Quick Web Notes ===
Contributors: aarifhsn
Tags: notes, sticky notes, to-do list, reminder, note-taking
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create and manage notes easily from both frontend and backend. Perfect for quick reminders, tasks, and ideas with drag-and-drop positioning.

== Description ==

Quick Web Notes transforms how you capture and manage ideas in WordPress. Whether you're writing content, managing tasks, or brainstorming, this plugin provides a seamless note-taking experience from both the frontend and backend of your website.

= Key Features =

* **Frontend Note Creation**: Add notes directly from any page without accessing the dashboard
* **Shortcode Support**: Display your notes anywhere using [ahqwn_notes] shortcode
* **Icon Positioning**: Place the notes icon anywhere on your screen
* **Background Color and Icon Uploader**: Customize the notes icon and background color
* **Comprehensive Dashboard**: Manage all notes from a dedicated admin interface
* **Real-Time Updates**: Notes appear instantly without page reload
* **Mobile-Friendly**: Fully responsive design works on all devices


= Perfect For =

* Content creators tracking article ideas
* Developers marking bug fixes and improvements
* Project managers maintaining task lists
* Team collaboration and communication
* Personal reminders and to-dos

= Using Shortcode =

Display your notes anywhere in your content using the shortcode:
`[ahqwn_notes]`


= Pro Features (Coming Soon) =

* Note sharing between users
* Advanced categorization
* File attachments
* Note export/import
* Email notifications
* Priority levels
* Due dates
* Advanced shortcode options

== Installation ==

1. Upload the 'quick-web-notes' folder to your '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Quick Web Notes > Manage Notes' to create your first note
4. Also from Frontend, you will get a floating notes icon to create or edit notes
5. Go to 'Quick Web Notes > Settings' to reposition the notes icon, change the background color, and upload a custom icon
6. Start creating notes!
7. Use [ahqwn_notes] shortcode to display notes in your content

= Minimum Requirements =

* WordPress 6.2 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

== Frequently Asked Questions ==

= How do I create a note from the frontend? =

Click the floating notes icon visible on your website, enter your note content, and click save. The note will be instantly created and stored. Also from dashboard you can create notes.

= How can I display my notes in a post or page? =

In frontpage, You will get a floating notes icon to create or edit notes automatically after the Installation. Also you can show notes using the [ahqwn_notes] shortcode wherever you want the notes to appear.


= Is there a limit to how many notes I can create? =

No, you can create as many notes as you need. However, we recommend regular cleanup of completed or outdated notes for better organization.

= Can I change the appearance of the notes icon? =

Yes, you can customize the icon's color and upload a custom icon as well as change the position.

= Does this plugin slow down my website? =

No, Quick Web Notes is optimized for performance. It loads resources only when needed and uses WordPress best practices for efficiency.

== Screenshots ==

1. Frontend notes interface
2. Admin dashboard Notes overview
3. Settings panel
4. Frontend add new notes interface
5. Frontedn Edit notes interface

== Changelog ==
* Enhanced database query security by implementing $wpdb->prepare() for all SQL queries
* Added proper escaping and sanitization for database operations
* Improved naming conventions to prevent potential plugin conflicts
* Implemented unique prefixing across all plugin functions and classes
* Added security checks to prevent direct file access
* Removed unnecessary load_plugin_textdomain() call
* Refactored function and class names

= 1.0.4 =


= 1.0.3 =
* Minor updates on ownership verification

= 1.0.2 =
* Minor updates to plugin information (author details and ownership verification).

= 1.0.1 =
* Fixed: Added user authentication check to ensure frontend icon is only visible to logged-in administrators
* Security: Prevented scripts from loading for non-administrator users

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
First public release. Includes all core features for note management from both frontend and backend, including shortcode support.

== Support ==

For support requests and bug reports, please mail us:
https://mountaviary.com/contact/

== Privacy Policy ==

Quick Web Notes stores notes in your WordPress database. No data is sent to external servers. Notes are visible only to authorized users based on your settings.

== Credits ==

Icon Attribution:
The plugin icon is provided by Flaticon.com
License: Free for personal and commercial use with attribution
Attribution: "Icon made by Kiranshastry from www.flaticon.com"

Screenshots:
- All screenshots are taken from the plugin's actual functionality and interface

Banners:
- All banners are taken from the plugin's actual functionality and interface
=== Plugin Name ===
Tags: tincan api, learning record store, Scorm Cloud, LMS, Learning Management System
Requires at least: 3.7.0
Tested up to: 3.7.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP-Tincan is a powerful teaching tool developed to help teachers track progress and study the users who are learning from them. Teachers may create courses, lessons, and quizzes and is integrated with BBpress.

== Description ==

WP-Tincan is a powerful teaching tool developed to help teachers track progress and study the users who are learning from them. Teachers may create courses, lessons, and quizzes and is integrated with BBpress. The teacher may upload PDF’s and podcasts as well to help get the lesson to users. When a user reads a lesson, views a course, or takes a quiz, WP-Tincan will automatically send a Tincan statement to the teacher saying which user did what. Using WP-Tincan will hopefully help the teacher understand what the user taking their course needs by letting the teacher know exactly what the user is doing.

== Installation ==

This section describes how to install the plugin and get it working.

1. Unzip wp-tincan.zip
2. Upload the unzipped files to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Register for a FREE or Premium Scorm Cloud LRS Access at https://cloud.scorm.com/sc/guest/SignUpForm
	( Upon Gaining Access, All Info Needed for Step 5 is at https://cloud.scorm.com/sc/user/LRSView
5. Configure your LRS ( Learning Record Store ) Settings using the WP-Tincan > "Tincan LRS Settings" Dashboard Link in your Wordpress Dashboard
6. Configure Your Wp-Tincan Settings:
7. Create a New Certificate and paste the following code inside of your Content Box ( Use 'text' view NOT visual view )

<h1 style="text-align: center;"><span style="color: #333333;">CERTIFICATE OF COMPLETION</span></h1> <h3 style="text-align: center;">[site_name]</h3> <p style="text-align: center;"><strong>HEREBY PRESENTS</strong></p> <h1 style="text-align: center;">[student]</h1> <address style="text-align: center;">With This Certificate in Recognition of Successful Completion of <strong>[course]</strong></address> <p style="text-align: center;">Awarded on [date]</p> 

8. Create Courses
9. Create Quizzes by Adding Individual Quiz Questions ( Keep in mind, a custom field is added for question answer possibilities that need to be filled out ) and Assigning each question to its appropriate "Quiz" ( quiz_categories ) taxonomy. Once the questions are made, simply go to the WP-Tincan > Tincan Quiz Settings and Copy and paste into any content ( preferably a course )
10. Watch your Scorm Cloud LRS periodically and watch the tincan data come in!

Data that can be recorded to LRS:
1. Posts
2. Pages
3. Forums ( if bbpress is installed )
4. Quizzes and quiz scores and quiz attempts

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

== SPECIAL THANKS ==
Rakhitha Nimesh for his work on Wordpress Multiple Choice Quizzes
http://wp.tutsplus.com/author/rakhithanimesh/
http://wp.tutsplus.com/tutorials/plugins/integrating-multiple-choice-quizzes-in-wordpress-creating-the-frontend/

TinCanJS
Copyright 2012 Rustici Software

This product includes software developed at
Rustici Software (http://www.scorm.com/).
(wp-tincan/js/build/tincan.js)


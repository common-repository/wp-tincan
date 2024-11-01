<?php
/**
 * @package WP-Tincan
 * @version 1.0
 */
/*
Plugin Name: WP-Tincan
Author: Sagedread Designs, LLC
Version: 1.0
Author URI: https://rioranchowebsitedesign.com
Description: WP-Tincan is a powerful teaching tool developed to help teachers track progress and study the users who are learning from them. Tincan API statements are sent and recorded into an LRS enabling the teacher to have a great understanding of how the students are learning and interacting with the e-learning website.
*/

//QUIZZES
//THANKS TO Rakhitha Nimesh for the quiz class base.
//http://wp.tutsplus.com/author/rakhithanimesh/
//http://wp.tutsplus.com/tutorials/plugins/integrating-multiple-choice-quizzes-in-wordpress-creating-the-frontend/
//TinCanJS ( build/tincan.js )
//Copyright 2012 Rustici Software
//Rustici Software (http://www.scorm.com/).


class WP_Quiz {

    public $plugin_url;

    public function __construct() {
        $this->plugin_url = plugin_dir_url(__FILE__);


        add_action('init', array($this, 'wpq_add_custom_post_type'));
        add_action('add_meta_boxes', array($this, 'wpq_quiz_meta_boxes'));
        add_action('init', array($this, 'wpq_create_taxonomies'), 0);
		add_action('init', array($this, 'create_courses'), 0);


        add_action('admin_enqueue_scripts', array($this, 'wpq_admin_scripts'));
        add_action('save_post', array($this, 'wpq_save_quizes'));
        //add_action('admin_menu', array($this, 'wpq_plugin_settings'));


        add_action('wp_enqueue_scripts', array($this, 'wpq_frontend_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'wpq_frontend_styles'));
        add_action('wp_ajax_nopriv_get_quiz_results', array($this, 'get_quiz_results'));
        add_action('wp_ajax_get_quiz_results', array($this, 'get_quiz_results'));

       // add_action('the_content', array($this, "wpq_show_quiz"));
		add_shortcode("quiz", array($this, "wpq_show_quiz"));
    }
	


    public function wpq_add_custom_post_type() {

        $labels = array(
            'name' => 'Questions',
            'menu_name' => 'Quizzes',
            'add_new' =>'Add New',
            'add_new_item' => 'Add New Question',
            'new_item' => 'New Question',
            'all_items' => 'All Questions',
            'edit_item' => 'Edit Question',
            'view_item' => 'View Question',
            'search_items' => 'Search Questions',
            'not_found' => 'No Questions Found'
        );



        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'description' => 'Quizzes',
            'supports' => array('title', 'editor'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'publicly_queryable' => true,
            'exclude_from_search' => false,
            'has_archive' => true,
            'query_var' => true,
            'can_export' => true,
            'rewrite' => true,
            'capability_type' => 'post'
        );

        register_post_type('quiz', $args);
		
		 $clabels = array(
            'name' => 'Certificates',
            'menu_name' => 'Certificates',
            'add_new' =>'Add New',
            'add_new_item' => 'Add New Certificate',
            'new_item' => 'New Certificate',
            'all_items' => 'All Certificates',
            'edit_item' => 'Edit Certificate',
            'view_item' => 'View Certificate',
            'search_items' => 'Search Certificates',
            'not_found' => 'No Certificates Found'
        );



        $cargs = array(
            'labels' => $clabels,
            'hierarchical' => false,
            'description' => 'Certificates',
            'supports' => array('title', 'editor'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'publicly_queryable' => true,
            'exclude_from_search' => true,
            'has_archive' => true,
            'query_var' => true,
            'can_export' => true,
            'rewrite' => true,
            'capability_type' => 'post'
        );

        register_post_type('certificate', $cargs);
		
    }

    function wpq_admin_scripts() {

        wp_enqueue_script('jQuery');

        wp_register_script('quiz-admin', plugins_url('js/quiz.js', __FILE__), array('jquery'));
        wp_enqueue_script('quiz-admin');
    }

    function wpq_frontend_scripts() {

        wp_enqueue_script('jQuery');



        wp_register_script('rhino', plugins_url('js/rhinoslider-1.05.min.js', __FILE__), array('jquery'));
        wp_enqueue_script('rhino');

        wp_register_script('rhino-mousewheel', plugins_url('js/mousewheel.js', __FILE__), array('jquery'));
        wp_enqueue_script('rhino-mousewheel');

        wp_register_script('rhino-easing', plugins_url('js/easing.js', __FILE__), array('jquery'));
        wp_enqueue_script('rhino-easing');


        $quiz_duration = get_option('wpq_duration');
        $quiz_duration = ($quiz_duration != "") ? $quiz_duration : 300;



        wp_register_script('quiz', plugins_url('js/quiz.js', __FILE__), array('jquery'));
        wp_enqueue_script('quiz');

        $config_array = array(
            'ajaxURL' => admin_url('admin-ajax.php'),
            'quizNonce' => wp_create_nonce('quiz-nonce'),
            'quizDuration' => $quiz_duration,
            'plugin_url' => $this->plugin_url
        );

        wp_localize_script('quiz', 'quiz', $config_array);
    }

    function wpq_frontend_styles() {

        wp_register_style('rhino-base', plugins_url('css/rhinoslider-1.05.css', __FILE__));
        wp_enqueue_style('rhino-base');
    }

    function wpq_quiz_meta_boxes() {

        add_meta_box("quiz-answers-info", "Quiz Answers Info", array($this, 'wpq_quiz_answers_info'), "quiz", "normal", "high");
    }

    function wpq_quiz_answers_info() {

        global $post;

        $question_answers = get_post_meta($post->ID, "_question_answers", true);
        $question_answers = ($question_answers == '') ? array("", "", "", "", "") : json_decode($question_answers);

        $question_correct_answer = trim(get_post_meta($post->ID, "_question_correct_answer", true));



        $html = '<input type="hidden" name="question_box_nonce" value="' . wp_create_nonce(basename(__FILE__)) . '" />';
        $html .= '<table class="form-table">';
        $html .= '<tr><th><label>Correct Answer  </label></th>';
        $html .= '<td><select name="correct_answer" id="correct_answer" >';

        for ($i = 1; $i <= 5; $i++) {
            if ($question_correct_answer == $i) {
                $html .= "<option value='{$i}' selected >Answer {$i}</option>";
            } else {
                $html .= "<option value='{$i}'>Answer {$i}</option>";
            }
        }


        $html .= "</select></td></tr>";

        $index = 1;
        foreach ($question_answers as $question_answer) {

            $html .= "<tr><th style=''>
            <label for='Price'>Answer {$index}</label>
            </th>
            <td>
            <textarea name='quiz_answer[]' id='quiz_answer{$index}' >" . trim($question_answer) . "</textarea>
            </td></tr>";
            $index++;
        }




        $html .= "</tr>";
        $html .= '</table>';

        echo $html;
    }

    function wpq_save_quizes($post_id) {

        if (!wp_verify_nonce($_POST['question_box_nonce'], basename(__FILE__))) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if ('quiz' == $_POST['post_type'] && current_user_can('edit_post', $post_id)) {

            $question_answers = isset($_POST['quiz_answer']) ? ($_POST['quiz_answer']) : array();
            $filtered_answers = array();
            foreach ($question_answers as $answer) {
                array_push($filtered_answers, trim($answer));
            }
            $question_answers = json_encode($filtered_answers);


            $correct_answer = isset($_POST['correct_answer']) ? $_POST['correct_answer'] : "";

            update_post_meta($post_id, "_question_answers", $question_answers);
            update_post_meta($post_id, "_question_correct_answer", $correct_answer);
        } else {
            return $post_id;
        }
    }

    function wpq_create_taxonomies() {
        register_taxonomy(
                'quiz_categories', array( 'quiz','certificate'),
			array(
            'labels' => array(
                'name' => 'Quizzes',
                'add_new_item' => 'Add New Quiz',
                'new_item_name' => "New Quiz"
            ),
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true
                )
        );
		
    }
	
	function create_courses(){
	register_taxonomy(
                'tincancourses', 'lesson', array(
            'labels' => array(
                'name' => 'Courses',
                'add_new_item' => 'Add New Course',
                'new_item_name' => "New Course"
            ),
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true,
			'query_var'	=> true,
			'rewrite' => array( 'slug' => 'yourcourses')
                )
        );
	}

    function get_quiz_results() {
        $score = 0;
        $question_answers = $_POST["data"];
        $question_results = array();
        foreach ($question_answers as $ques_id => $answer) {
            $question_id = trim(str_replace("qid_", "", $ques_id)) . ",";

            $correct_answer = get_post_meta($question_id, '_question_correct_answer', true);
            if ($answer == $correct_answer) {
                $score++;
                $question_results["$question_id"] = array("answer" => $answer, "correct_answer" => $correct_answer, "mark" => "correct");
            } else {
                $question_results["$question_id"] = array("answer" => $answer, "correct_answer" => $correct_answer, "mark" => "incorrect");
            }
        }

        $total_questions = count($question_answers);
        $quiz_result_data = array(
            "total_questions" => $total_questions,
            "score" => $score,
            "result" => $question_results
        );
        echo json_encode($quiz_result_data);
        exit;
    }

    function wpq_show_quiz($atts) {
        global $post;
$thetype = get_post_type( get_the_ID() );
extract( shortcode_atts( array(
		'thequiz' => 'none'
	), $atts ) );
$certificate = get_cert($thequiz);
$current_user = wp_get_current_user();
	if( is_singular() ){
	if(!empty($current_user->ID) ){


        $html = "<div id='quiz_panel'>";
		if(!isset($_POST['quizstart']) and !isset($_POST['donequiz'])){
		$html .= "<form action='' method='POST' >";
        $html .= "<div class='toolbar'>";
        $html .= "<div class='toolbar_item'>";

        $quiz_categories = get_terms('quiz_categories', 'hide_empty=1');
        

        $html .= "</div>";
        $html .= "<input type='hidden' value='start' name='quizstart' />";
        $html .= "<div class='toolbar_item'><input type='submit' value='Start Quiz' /></div>";
        $html .= "</form>";
		
		}
if(isset($_POST['donequiz']) and !empty($_COOKIE['lastquiz'])){
sendquiz($thequiz);
if($_COOKIE['lastquiz'] == 'passed'){
if($certificate != ""){
$html .= "<a target='_blank' href='".$certificate."' style='font-size:18px;' >View Certificate</a><br />";
$html .= "For Proper Printing, Be sure to print in Landscape Format. Use Print Preview in Your Web Browser's Print Settings";
}else{
$html .= "There are currently no certificates available for this quiz.<a href='";
$html .= get_bloginfo('url')."' >Click Here</a>";
}
}else{
$html .= "You Did Not Pass, Redirecting...";
}
}elseif(isset($_POST['quizstart'])){
        //$html .= "<div class='complete toolbar_item' ><input type='button' id='completeQuiz' value='Get Results' /></div>";


        $questions_str = "";

            $html .= "<div id='timer' style='display:block' ></div>";
            $html .= "<div style='clear:both'></div></div>";

            $quiz_category_id = $thequiz;
			sendquizattempt($quiz_category_id);
            $quiz_num = get_option('wpq_num_questions');
            $args = array(
                'post_type' => 'quiz',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'quiz_categories',
                        'field' => 'slug',
                        'terms' => $quiz_category_id
                    )
                ),
                'orderby' => 'rand',
                'post_status' => 'publish',
                'posts_per_page' => $quiz_num
            );

            $query = null;
            $query = new WP_Query($args);
            $quiz_index = 1;
            while ($query->have_posts()) : $query->the_post();

                $question_id = get_the_ID();
                $question = the_title("", "", FALSE) . " " . get_the_content();

                $question_answers = json_decode(get_post_meta($question_id, "_question_answers", true));

                $questions_str .= "<li>";
                $questions_str .= "<div class='ques_title'><span class='quiz_num'>{$quiz_index}</span>{$question}</div>";
                $questions_str .= "<div class='ques_answers' data-quiz-id='{$question_id}' >";

                $quiestion_index = 1;
                foreach ($question_answers as $key => $value) {

                    if ($value != "") {
                        $questions_str .= "{$quiestion_index} <input type='radio' value='{$quiestion_index}' name='ans_{$question_id}[]' />{$value}<br/>";
                    }
                    $quiestion_index++;
                }

                $questions_str .= "</div></li>";

                $quiz_index++;

            endwhile;


            wp_reset_query();



            $html .= "<ul id='slider'>{$questions_str}";
            $html .= "<li id='quiz_result_page'><div class='complete toolbar_item' ><input type='button' id='completeQuiz' style='width:100%;' value='Grade My Quiz' /><form action=''  method='POST' ><input type='submit' id='done' name='donequiz' style='display:none;width:100%;' value='Click Here to Finish the Quiz' /></form></div><div class='ques_title'>Quiz Results <span id='score'></span></div>";
            $html .= "<div id='quiz_result'></div>";
            $html .= "</li></ul>";
			}
			$html .= "</div>";
         return $html;
		}else{ return "<h2>You must be logged in to take a quiz</h2>"; }
    }else{ return; }

}}

$quiz = new WP_Quiz();


 function wpq_display_settings() {


        $html = '<div class="wrap">

            <form method="post" name="options" action="options.php">

            <h2>Select Your Settings</h2>' . wp_nonce_field('update-options') . '
            <table width="100%" cellpadding="10" class="form-table">
                <tr>
                    <td align="left" scope="row">
                    <label>Maximum Number of Questions Per Quiz</label><input type="text" name="wpq_num_questions" 
                        value="' . get_option('wpq_num_questions') . '" />

                    </td> 
                </tr>
                <tr>
                    <td align="left" scope="row">
                    <label>Duration (Mins) to complete each quiz</label><input type="text" name="wpq_duration" 
                    value="' . get_option('wpq_duration') . '" />

                    </td> 
                </tr>
            </table>
            <p class="submit">
                <input type="hidden" name="action" value="update" />  
                <input type="hidden" name="page_options" value="wpq_num_questions,wpq_duration" /> 
                <input type="submit" name="Submit" value="Update" />
            </p>
            </form>

        ';
		$html .= "<h1>Currently Available Quiz Shortcodes</h1><br /><h2>Copy and Paste the Following Shortcode Into Any Lesson. That lesson will then display the quiz..</h2>";
		$html .= "Quizzes without any Questions will NOT be shown here.";
		 $quiz_categories = get_terms('quiz_categories', 'hide_empty=1');
		 $html .= "<ul>";
        foreach ($quiz_categories as $quiz_category) {
            $html .= "<li><code>[quiz thequiz='".$quiz_category->slug."' ]</code></li>";
        }
		$html .= "</ul></div>";
        echo $html;

    }
//returns a url -- only the first one	
function get_cert($tax){
$custom_terms = get_terms('quiz_categories');

foreach($custom_terms as $custom_term) {
    wp_reset_query();
    $args = array('post_type' => 'certificate',
        'tax_query' => array(
            array(
                'taxonomy' => 'quiz_categories',
                'field' => 'slug',
                'terms' => $tax,
            ),
        ),
     );

     $loop = new WP_Query($args);
     if($loop->have_posts()) {
        while($loop->have_posts()) : $loop->the_post();

            return get_permalink();
			
        endwhile;
     }
}
}


/* ADD META BOXES */
/* Define the custom box */
function custom_init() {
  $labels = array(
    'name' => 'Lessons',
    'singular_name' => 'Lesson',
    'add_new' => 'Add New',
    'add_new_item' => 'Add New Lesson',
    'edit_item' => 'Edit Lesson',
    'new_item' => 'New Lesson',
    'all_items' => 'All Lessons',
    'view_item' => 'View Lesson',
    'search_items' => 'Search Lessons',
    'not_found' =>  'No lessons found',
    'not_found_in_trash' => 'No lessons found in Trash', 
    'parent_item_colon' => '',
    'menu_name' => 'Lessons'
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => true, 
    'query_var' => true,
    'rewrite' => array( 'slug' => 'lesson' ),
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
  ); 

  register_post_type( 'lesson', $args );
}
add_action( 'init', 'custom_init' );
add_action( 'add_meta_boxes', 'tincan_add_custom_box' );

// backwards compatible (before WP 3.0)
// add_action( 'admin_init', 'tincan_add_custom_box', 1 );

/* Do something with the data entered */
add_action( 'save_post', 'Tincan_save_postdata' );

/* Adds a box to the main column on the Post and Page edit screens */
function tincan_add_custom_box() {
    $screens = array( 'post', 'page', 'forum', 'lesson' );
    foreach ($screens as $screen) {
        add_meta_box(
            'myplugin_sectionid',
            __( 'Tin Can Plugin Settings', 'myplugin_textdomain' ),
            'myplugin_inner_custom_box',
            $screen
        );
    }
}

/* Prints the box content */
function myplugin_inner_custom_box( $post ) {

  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'WPtincan' );

  // The actual fields for data entry
  // Use get_post_meta to retrieve an existing value from the database and use the value for the form
  $value = get_post_meta( $post->ID, 'tincan', true );
  echo '<label for="myplugin_new_field">';
       _e("Post Tincan Statements for this Content?", 'myplugin_textdomain' );
  echo '</label> ';
    if(esc_attr($value) == "yes"){
  echo '&nbsp;&nbsp;<strong>Yes</strong>&nbsp;&nbsp;<input type="radio" id="myplugin_new_field" name="myplugin_new_field" value="yes" size="25" checked="checked" />&nbsp;&nbsp;<strong>No</strong>&nbsp;&nbsp;<input type="radio" id="myplugin_new_field" name="myplugin_new_field" value="no" size="25" />';
  }else{
    echo '&nbsp;&nbsp;<strong>Yes</strong>&nbsp;&nbsp;<input type="radio" id="myplugin_new_field" name="myplugin_new_field" value="yes" size="25" />&nbsp;&nbsp;<strong>No</strong>&nbsp;&nbsp;<input type="radio" id="myplugin_new_field" name="myplugin_new_field" value="no" size="25" checked="checked" />';
  }
  echo '<div class="clear" ></div>';
     $currtopic = get_post_meta( $post->ID, 'tincan_topic', true );
	 if(!isset($currtopic)){$currtopic = "";}
  echo '<label for="tincantopics" >Topic';

  echo '</label>';
    echo '<div class="clear" ></div>';
  echo '<select name="tincantopics" id="tincantopics" >';
  $pretopics = get_option('tincan_topics');
  $topics = explode(",",$pretopics);
  foreach($topics as $topic){
  echo '<option name="tincantopics" id="tincantopics" value="'.$topic.'" >'.$topic.'</option>';
  }
      echo '<option name="tincantopics" id="tincantopics"  value="'.$currtopic.'" selected="selected">Currently: '.$currtopic.'</option>';
  echo '</select>';
  echo '<div class="clear" ></div>';
  echo '<label for="additionaltopic" >Or Add New Topic</label><input type="text" name="additionaltopic" /><div class="clear" ></div>';

  echo '<a href="'.get_bloginfo('url').'/wp-admin/options-general.php?page=tincan-lrs-settings'.'" >Change TinCan Settings and Update Topics List</a>';
  
  
}

/* When the post is saved, saves our custom data */
function Tincan_save_postdata( $post_id ) {

  // First we need to check if the current user is authorised to do this action. 
  if ( 'page' == $_REQUEST['post_type'] ) {
    if ( ! current_user_can( 'edit_page', $post_id ) )
        return;
  } else {
    if ( ! current_user_can( 'edit_post', $post_id ) )
        return;
  }

  // Secondly we need to check if the user intended to change this value.
  if ( ! isset( $_POST['WPtincan'] ) || ! wp_verify_nonce( $_POST['WPtincan'], plugin_basename( __FILE__ ) ) )
      return;

  // Thirdly we can save the value to the database

  //if saving in a custom table, get post_ID
  $post_ID = $_POST['post_ID'];
  //sanitize user input
  $mydata = sanitize_text_field( $_POST['myplugin_new_field'] );
  $seltopic = sanitize_text_field( $_POST['tincantopics'] );
  $newtopic = sanitize_text_field( $_POST['additionaltopic'] );
  
  if($newtopic != ""){
  	$tincan_topics = get_option('tincan_topics');
	$newtincan_topics = $tincan_topics.",".$newtopic;
	update_option( 'tincan_topics', $newtincan_topics);
	$seltopic = $newtopic;
  }

  // Do something with $mydata 
  // either using 
  add_post_meta($post_ID, 'tincan', $mydata, true) or
    update_post_meta($post_ID, 'tincan', $mydata);
	
	  add_post_meta($post_ID, 'tincan_topic', $seltopic, true) or
    update_post_meta($post_ID, 'tincan_topic', $seltopic);
  // or a custom table (see Further Reading section below)
}


/*END ADD META BOXES*/
function full_url()
{
$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
$uri = $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
$segments = explode('?', $uri, 2);
$url = $segments[0];

$parts = parse_url($url);

$str = $parts['scheme'].'://'.$parts['host'].$parts['path'];

return $str;
}

function parser($url){

$parts = parse_url($url);

$str = $parts['scheme'].'://'.$parts['host'].$parts['path'];

return $str;
}

function myJson_encode($str)
{
	return str_replace('\\/', '/',json_encode($str));
}
add_action('init','js_init');
//print get_bloginfo('url');
//print rtrim(full_url(),'/');

if(isset($_COOKIE[ "comment" ])){
if($_COOKIE[ "comment" ] == full_url()){
   add_action('the_post', 'sendcomment'); 
    }
	}
	
add_action('loop_start','start');
add_action('loop_start','setCourse');
function setCourse(){
$terms = get_the_terms( $post->ID, 'tincancourses' );
	if(!isset($_POST['quizstart']) and !isset($_POST['donequiz'])){
if($terms && ! is_wp_error($terms)){
$term = array_pop($terms);

	wp_register_script('tincourse', plugins_url('/tincourses.js',__FILE__));
	wp_enqueue_script('tincourse');
	wp_localize_script('tincourse', 'vars', array(
			'course' => $term->name,
		)
	);
	}
	}
}

/*CHECK IF IS FRONT PAGE OR NOT..IF NOT, PROCEED */	
function start(){

if(rtrim(full_url(),'/') != get_bloginfo('url')){
add_action('parse_request','action_init');
$value = get_post_meta( get_the_ID() , 'tincan', true );
if(esc_attr($value) == 'yes'){ //VERIFY THAT POST TYPE HAS TINCAN SENDING ENABLED
if(!isset($_COOKIE['experienced'])){
//add_action('wp', 'sendblogpost');
$thetype = get_post_type( get_the_ID() );
if( $thetype == "post" or $thetype == "lesson" or $thetype == "page"){
if( is_singular(array( 'post', 'lesson','page' )) ){
sendblogpost();
}
}
if( $thetype == "forum" ){
if( is_singular('forum') ){
sendforum();
}}

}
}
//global $wp_query;
//$postid = $wp_query->post->ID;
//$type =  get_post_type_object(get_post_type( $postid));
}
}
if(get_option('tincan_comments') == "yes"){ //CHECK TO SEE IF COMMENTS ARE BEING SENT IN SETTINGS
add_filter('comment_post_redirect','qcomment');
}
function action_init() {
if(get_option('tincan_structure') == "no"){
if(!isset($_COOKIE['experienced'])){
   setcookie( 'experienced', full_url());
   }
   }
}
function qcomment() {
   $location = empty($_POST['redirect_to']) ? get_comment_link($comment_id, array('type' => 'comment')) : $_POST['redirect_to'] . '#comment-' . $comment_id;
   $cookloc = parser(get_comment_link($comment_id, array('type' => 'comment')));
   setcookie( "comment",$cookloc,time() + (60));
   return $location;
}
function js_init() {
wp_enqueue_script( 'base64', plugins_url( '/base64.js', __FILE__ ));
      wp_enqueue_script('jQuery');
    wp_enqueue_script( 'tincan', plugins_url( '/js/build/tincan-min.js', __FILE__ ));

}

function sendstatement($activity, $theverb, $verbdisplay,$course,$description) {
 //activity = http://adlnet.gov/expapi/activities/
 //_verb = http://adlnet.gov/expapi/verbs/
 //verbdisplay = How to display verb
 $verb = $theverb;
$adltype = $activity;
$display = $verbdisplay;
 $current_user = wp_get_current_user();
	
	if(!empty($current_user->ID)  ){
	if(!empty($current_user->user_firstname) ){
$actorName = (string)$current_user->user_firstname." ".$current_user->user_lastname;
}elseif(!empty($current_user->display_name)){
$actorName = (string)$current_user->display_name;
}else{
$actorName = (string)$current_user->user_login;
}
$actorEmail = $current_user->user_email; 
	wp_register_script('tin', plugins_url('/functions.js',__FILE__));
	wp_enqueue_script('tin');
	wp_localize_script('tin', 'vars', array(
			'endpoint' => get_option('tincan_endpoint'),
			'user' => get_option('tincan_user'),
			'pass' => get_option('tincan_password'),
			'actorname' => $actorName,
			'email' => $actorEmail,
			'display' => $display,
			'coursename' => $course,
			'description' => $description,
			'verb' => $verb,
			'adltype' => $adltype,
			'redir' => get_bloginfo('url')
		)
	);
	

	}
 
}

function sendblogpost(){
if(get_option('tincan_structure') == "yes"){
$course = (string) get_post_meta( get_the_ID() , 'tincan_topic', true );
$thecourse = strtolower( $course );
if(!isset($_COOKIE[$thecourse])){
   sendstatement('link', 'experienced','experienced topic',$course,$thecourse);
   }
   }else{
      sendstatement('link', 'experienced','experienced',(string)get_the_title(),(string)get_the_excerpt());
   }
}
function sendquizattempt($quiz){
$pretitle = get_term_by('slug', $quiz, 'quiz_categories');
$title = $pretitle->name;
$ds = "quiz";
if(isset($_COOKIE['currentcourse']) and $_COOKIE['currentcourse'] != " "){
$title = $_COOKIE['currentcourse'];
$ds = "course";
}
if(!isset($_COOKIE[$quiz]) or empty($_COOKIE[$quiz])){
	  if($ds == "quiz"){$display = "launched quiz";}if($ds == "course"){$display = "launched the quiz for course:";}
      sendstatement('assessment', 'launched',$display,$title,$quiz);
	  }
}
function sendquiz($quiz){
$pretitle = get_term_by('slug', $quiz, 'quiz_categories');
$title = $pretitle->name;
$ds = "quiz";
if(isset($_COOKIE['currentcourse']) and $_COOKIE['currentcourse'] != " "){
$title = $_COOKIE['currentcourse'];
$ds = "course";
}
if(isset($_COOKIE['lastquiz']) or empty($_COOKIE['lastquiz'])){
	  if($_COOKIE['lastquiz'] == "passed" ){
	  if($ds == "quiz"){$display = "passed quiz";}if($ds == "course"){$display = "passed course";}
	    sendstatement('assessment', 'passed',$display,$title,$quiz);
	  }
	  if($_COOKIE['lastquiz'] == "failed" ){
	  	  if($ds == "quiz"){$display = "failed quiz";}if($ds == "course"){$display = "failed course";}
	    sendstatement('assessment', 'failed',$display,$title,$quiz);
	  }
	  }
}
function sendforum(){
if(get_option('tincan_structure') == "yes"){
$course = (string) get_post_meta( get_the_ID() , 'tincan_topic', true );
$thecourse = strtolower( $course );
   sendstatement('link', 'experienced','experienced forum topic',$course,$thecourse);
   }else{
   sendstatement('link', 'experienced','experienced forum',(string)get_the_title(),(string)get_the_excerpt());
   }
}
function sendcomment(){
if(get_option('tincan_structure') == "yes"){
$course = (string) get_post_meta( get_the_ID() , 'tincan_topic', true );
$thecourse = strtolower( $course );
   sendstatement('link', 'commented','commented on topic',$course,$thecourse);
   }else{
   sendstatement('link', 'commented','commented on',(string)get_the_title(),(string)get_the_excerpt());
   }
}


add_action('admin_menu', 'tincanviewer_menu');
function tincanviewer_menu() {
   add_menu_page( 'TinCan', 'WP-Tincan', 'manage_options', 'tincanpage', 'tincanmenu', plugins_url( 'tincan/icon.png' ), 6 ); 
add_submenu_page("tincanpage", "TinCan LRS Settings", "TinCan LRS Settings",'manage_options','tincan-lrs-settings', 'tincanviewer_menu_page');
add_submenu_page("tincanpage", "TinCan Quiz Settings", "TinCan Quiz Settings",'manage_options','tincan-quiz-settings', 'wpq_display_settings');
    //    add_submenu_page('tincanpage', 'Quiz Settings', 'administrator', 'quiz_settings', 'wpq_display_settings');
}

function tincanmenu(){
        //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    //Read in existing option value from database
	$tincan_comm = get_option('tincan_comments');
	$tincan_str =  get_option('tincan_structure');
	$tincan_topics =  get_option('tincan_topics');
	
    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ "update_TinCanLRS" ]) ) {
        // Read their posted value
		$tincan_comm = $_POST['tincan_comments'];
		$tincan_str = $_POST['tincan_structure'];
		$tincan_topics = $_POST['tincan_topics'];

        // Save the posted value in the database
		update_option( 'tincan_comments', $tincan_comm);
		update_option( 'tincan_structure', $tincan_str);
		update_option( 'tincan_topics', $tincan_topics);
        // Put an settings updated message on the screen

?>
<div class="updated"><p><strong><?php _e('Settings Saved.', 'TinCanLRSSettings' ); ?></strong></p></div>
<?php

    }
?>
<div class=wrap>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<h2>TinCan Learning Management System</h2>
<h3>Topics</h3>
<h4>Each Topic Name Separated by a Comma. PLEASE NOTE: The last character in this box should not be a comma!</h4>
<textarea cols=75 rows=10 name="tincan_topics" id="tincan_topics" >
<?php echo $tincan_topics; ?>
</textarea>
<h3>TinCan Statement Settings</h3>
<label for="tincan_comments" >Send Tincan Statements for Comments?</label>
<input type="checkbox" value="yes" id="tincan_comments" name="tincan_comments" <?php if($tincan_comm == "yes"){echo "checked='checked'"; }?>/>&nbsp;&nbsp;<strong>Yes</strong>
<div class="clear" ></div>
<label for="tincan_structure" >Use Topics Instead of Title in Tincan Statement?</label>
<input type="checkbox" value="yes" id="tincan_structure" name="tincan_structure" <?php if($tincan_str == "yes"){echo "checked='checked'"; }?>/>&nbsp;&nbsp;<strong>Yes</strong>
<div class="submit">
<input type="submit" name="update_TinCanLRS" value="<?php _e('Update Settings', 'TinCanLRSSettings') ?>" /></div>
</form>
<h2>Certificate Template</h2>
Copy and Paste the Following into the 'text' (NOT VISUAL) tab in any Certificate<br /><br />
<code style="width:150px;overflow:auto;">
			&lt;h1 style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;color: #333333;&quot;&gt;CERTIFICATE OF COMPLETION&lt;/span&gt;&lt;/h1&gt; &lt;h3 style=&quot;text-align: center;&quot;&gt;[site_name]&lt;/h3&gt; &lt;p style=&quot;text-align: center;&quot;&gt;&lt;strong&gt;HEREBY PRESENTS&lt;/strong&gt;&lt;/p&gt; &lt;h1 style=&quot;text-align: center;&quot;&gt;[student]&lt;/h1&gt; &lt;address style=&quot;text-align: center;&quot;&gt;With This Certificate in Recognition of Successful Completion of &lt;strong&gt;[course]&lt;/strong&gt;&lt;/address&gt; &lt;p style=&quot;text-align: center;&quot;&gt;Awarded on [date]&lt;/p&gt;
</code>
 </div>
<?php
}
function tincanviewer_menu_page() {
    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    //Read in existing option value from database
    $tincan_endpoint = get_option('tincan_endpoint');
    $tincan_user = get_option('tincan_user');
    $tincan_password = get_option('tincan_password');
	
    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ "update_TinCanLRSSettings" ]) ) {
        // Read their posted value
        $tincan_user = $_POST['tincan_user'];
		$tincan_endpoint = $_POST['tincan_endpoint'];
        $tincan_password = $_POST['tincan_password'];

        // Save the posted value in the database
        update_option( 'tincan_user', $tincan_user);
        update_option( 'tincan_password', $tincan_password);
		update_option( 'tincan_endpoint', $tincan_endpoint);
        // Put an settings updated message on the screen

?>
<div class="updated"><p><strong><?php _e('Settings Saved.', 'TinCanLRSSettings' ); ?></strong></p></div>
<?php

    }
?>
<div class=wrap>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<h2>TinCan LRS Settings</h2>
<h3>Tested with SCORM Cloud LRS</h3>
<h3>LRS Endpoint ( MUST end with a backslash / ):</h3>
<input name="tincan_endpoint" style="min-width:30%" value="<?php _e(apply_filters('format_to_edit',$tincan_endpoint), 'TinCanLRSSettings') ?>" />
<h3>LRS Activity Provider KEY:</h3>
<input name="tincan_user" style="min-width:30%" value="<?php _e(apply_filters('format_to_edit',$tincan_user), 'TinCanLRSSettings') ?>" />
<h3>LRS Activity Provider Secret ( Use BASIC Auth ):</h3>
<input name="tincan_password" style="min-width:30%" value="<?php _e(apply_filters('format_to_edit',$tincan_password), 'TinCanLRSSettings') ?>" />
<br />
<div class="submit">
<input type="submit" name="update_TinCanLRSSettings" value="<?php _e('Update Settings', 'TinCanLRSSettings') ?>" /></div>
</form>

 </div>
<?php
}

// Add settings link on plugin page
function tincan_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=tincan-lrs-settings">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'tincan_plugin_settings_link' );


//SHORTCODES FOR CERTIFICATES

if ( !function_exists('bloginfo_shortcode') ) {
	function bloginfo_shortcode( $atts ) {
		extract(shortcode_atts(array(
			'show' => 'name'
		), $atts));
		return get_bloginfo($show);
	}
	add_shortcode('bloginfo', 'bloginfo_shortcode');
}
if ( !function_exists('quizcode') ) {
	function quizcode( $atts ) {
		extract(shortcode_atts(array(
			'show' => 'name'
		), $atts));
		if(isset($_COOKIE['forcert'])){
		
		$pretitle =  $_COOKIE['forcert'];
		$title = get_term_by('slug', $pretitle, 'quiz_categories');
		return $title->name;
		}
	}
	add_shortcode('passedquiz', 'quizcode');
}

if ( !function_exists('coursecode') ) {
	function coursecode( $atts ) {
		extract(shortcode_atts(array(
			'show' => 'name'
		), $atts));
		if(isset($_COOKIE['forcert'])){
		return $_COOKIE['forcert'];
		}
	}
	add_shortcode('course', 'coursecode');
}

if ( !function_exists('shortuser') ) {
	function shortuser( $atts ) {
		extract(shortcode_atts(array(
			'show' => 'fullname'
		), $atts));
$current_user = wp_get_current_user();
	if(!empty($current_user->ID) ){
	if($current_user->user_firstname != ""){
		$name = $current_user->user_firstname;
		$lastname = $current_user->user_lastname;
		return $name." ".$lastname;
	}else{ return $current_user->user_login; }
	}
	
	}
	
	add_shortcode('student', 'shortuser');
}

if ( !function_exists('site_name_shortcode') ) {
	function site_name_shortcode( $atts ) {
		return get_bloginfo('name');
	}
	add_shortcode( 'site_name', 'site_name_shortcode' );
	add_shortcode( 'sitename', 'site_name_shortcode' ); // just in case
	add_shortcode( 'site_title', 'site_name_shortcode' ); // just in case
	add_shortcode( 'sitetitle', 'site_name_shortcode' ); // just in case
	//add_shortcode( 'site-name', 'site_name_shortcode' ); // not good (Shortcode names should be all lowercase and use all letters, but numbers and underscores (not dashes!) should work fine too.)
}

if ( !function_exists('site_desc_shortcode') ) {
	function site_desc_shortcode( $atts ) {
		return get_bloginfo('description');
	}
	add_shortcode( 'site_desc', 'site_desc_shortcode' );
	add_shortcode( 'sitedesc', 'site_desc_shortcode' ); // just in case
	add_shortcode( 'site_description', 'site_desc_shortcode' ); // just in case
	add_shortcode( 'sitedescription', 'site_desc_shortcode' ); // just in case
	//add_shortcode( 'site-desc', 'site_desc_shortcode' ); // not good (Shortcode names should be all lowercase and use all letters, but numbers and underscores (not dashes!) should work fine too.)
}

if ( !function_exists('site_url_shortcode') ) {
	function site_url_shortcode( $atts ) {
		return get_bloginfo('url');
	}
	add_shortcode( 'site_url', 'site_url_shortcode' );
	add_shortcode( 'siteurl', 'site_url_shortcode' ); // just in case
	//add_shortcode( 'site-url', 'site_url_shortcode' ); // not good (Shortcode names should be all lowercase and use all letters, but numbers and underscores (not dashes!) should work fine too.)
}

if ( !function_exists('wp_version_shortcode') ) {
	function wp_version_shortcode( $atts ) {
		return get_bloginfo('version');
	}
	add_shortcode( 'wp_version', 'wp_version_shortcode' );
	add_shortcode( 'wpversion', 'wp_version_shortcode' ); // just in case
	//add_shortcode( 'wp-version', 'wp_version_shortcode' ); // not good (Shortcode names should be all lowercase and use all letters, but numbers and underscores (not dashes!) should work fine too.)
}

if ( !function_exists('date_shortcode') ) {
	function date_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'format' => 'l jS \of F Y',
			'timestamp' => 'now'
		), $atts ) );
		return date( $format, strtotime( $timestamp ) );
	}
	add_shortcode( 'date', 'date_shortcode' );
}

if ( !function_exists('date_i18n_shortcode') ) {
	function date_i18n_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'format' => 'l jS \of F Y',
			'timestamp' => 'now'
		), $atts ) );
		return date_i18n( $format, strtotime( $timestamp ) );
	}
	add_shortcode( 'date', 'date_i18n_shortcode' );
}

if ( !function_exists('time_shortcode') ) {
	function time_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'format' => 'h:i:s A',
			'timestamp' => 'now'
		), $atts ) );
		return date( $format, strtotime( $timestamp ) );
	}
	add_shortcode( 'time', 'time_shortcode' );
}

if ( !function_exists('year_shortcode') ) {
	function year_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'plus' => 0,
			'minus' => 0,
			'timestamp' => 'now'
		), $atts ) );
		if( !empty( $plus ) ){
			$year = date( 'Y', strtotime( '+'.intval($plus).' years' ) );
		}elseif( !empty( $minus ) ){
			$year = date( 'Y', strtotime( '-'.intval($minus).' years' ) );
		}else{
			$year = date( 'Y', strtotime( $timestamp ) );
		}
		return $year;
	}
	add_shortcode( 'year', 'year_shortcode' );
}

if ( !function_exists('month_shortcode') ) {
	function month_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'plus' => 0,
			'minus' => 0,
			'timestamp' => 'now'
		), $atts ) );
		if( !empty( $plus ) ){
			$month = date( 'm', strtotime( '+'.intval($plus).' months' ) );
		}elseif( !empty( $minus ) ){
			$month = date( 'm', strtotime( '-'.intval($minus).' months' ) );
		}else{
			$month = date( 'm', strtotime( $timestamp ) );
		}
		return $month;
	}
	add_shortcode( 'month', 'month_shortcode' );
}

if ( !function_exists('month_name_shortcode') ) {
	function month_name_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'plus' => 0,
			'minus' => 0,
			'timestamp' => 'now'
		), $atts ) );
		if( !empty( $plus ) ){
			$month_name = date( 'F', strtotime( '+'.intval($plus).' months' ) );
		}elseif( !empty( $minus ) ){
			$month_name = date( 'F', strtotime( '-'.intval($minus).' months' ) );
		}else{
			$month_name = date( 'F', strtotime( $timestamp ) );
		}
		return $month_name;
	}
	add_shortcode( 'month_name', 'month_name_shortcode' );
	add_shortcode( 'monthname', 'month_name_shortcode' ); // just in case
	//add_shortcode( 'month-name', 'month_name_shortcode' ); // not good (Shortcode names should be all lowercase and use all letters, but numbers and underscores (not dashes!) should work fine too.)
}

if ( !function_exists('day_shortcode') ) {
	function day_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'plus' => 0,
			'minus' => 0,
			'timestamp' => 'now'
		), $atts ) );
		if( !empty( $plus ) ){
			$day = date( 'd', strtotime( '+'.intval($plus).' days' ) );
		}elseif( !empty( $minus ) ){
			$day = date( 'd', strtotime( '-'.intval($minus).' days' ) );
		}else{
			$day = date( 'd', strtotime( $timestamp ) );
		}
		return $day;
	}
	add_shortcode( 'day', 'day_shortcode' );
}

if ( !function_exists('weekday_shortcode') ) {
	function weekday_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'plus' => 0,
			'minus' => 0,
			'timestamp' => 'now'
		), $atts ) );
		if( !empty( $plus ) ){
			$weekday = date( 'l', strtotime( '+'.intval($plus).' days' ) );
		}elseif( !empty( $minus ) ){
			$weekday = date( 'l', strtotime( '-'.intval($minus).' days' ) );
		}else{
			$weekday = date( 'l', strtotime( $timestamp ) );
		}
		return $weekday;
	}
	add_shortcode( 'weekday', 'weekday_shortcode' );
	add_shortcode( 'week_day', 'weekday_shortcode' ); // just in case
	//add_shortcode( 'week-day', 'weekday_shortcode' ); // not good (Shortcode names should be all lowercase and use all letters, but numbers and underscores (not dashes!) should work fine too.)
}

if ( !function_exists('hours_shortcode') ) {
	function hours_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'plus' => 0,
			'minus' => 0,
			'timestamp' => 'now'
		), $atts ) );
		if( !empty( $plus ) ){
			$hours = date( 'H', strtotime( '+'.intval($plus).' hours' ) );
		}elseif( !empty( $minus ) ){
			$hours = date( 'H', strtotime( '-'.intval($minus).' hours' ) );
		}else{
			$hours = date( 'H', strtotime( $timestamp ) );
		}
		return $hours;
	}
	add_shortcode( 'hours', 'hours_shortcode' );
	add_shortcode( 'hour', 'hours_shortcode' ); // just in case
}

if ( !function_exists('minutes_shortcode') ) {
	function minutes_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'plus' => 0,
			'minus' => 0,
			'timestamp' => 'now'
		), $atts ) );
		if( !empty( $plus ) ){
			$minutes = date( 'i', strtotime( '+'.intval($plus).' minutes' ) );
		}elseif( !empty( $minus ) ){
			$minutes = date( 'i', strtotime( '-'.intval($minus).' minutes' ) );
		}else{
			$minutes = date( 'i', strtotime( $timestamp ) );
		}
		return $minutes;
	}
	add_shortcode( 'minutes', 'minutes_shortcode' );
	add_shortcode( 'minute', 'minutes_shortcode' ); // just in case
}

if ( !function_exists('seconds_shortcode') ) {
	function seconds_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'plus' => 0,
			'minus' => 0,
			'timestamp' => 'now'
		), $atts ) );
		if( !empty( $plus ) ){
			$seconds = date( 's', strtotime( '+'.intval($plus).' seconds' ) );
		}elseif( !empty( $minus ) ){
			$seconds = date( 's', strtotime( '-'.intval($minus).' seconds' ) );
		}else{
			$seconds = date( 's', strtotime( $timestamp ) );
		}
		return $seconds;
	}
	add_shortcode( 'seconds', 'seconds_shortcode' );
	add_shortcode( 'second', 'seconds_shortcode' ); // just in case
}

//SHOW ONLY CERTS
add_action( 'template_redirect', 'template_filter' );

	/**
	 * Hook to check for meta and call template filter
	 */
	function template_filter() {

		//if not a page or single post, kick
		if ( is_singular('certificate') ){
			
		remove_filter( 'the_content', 'wpautop' );
		add_filter('template_include', 'template_callback' , 100);
	
	}
	}
	
	/**
	 * Callback to replace the current template with our blank template
	 * @return string the path to the plugin's template.php
	 */
	function template_callback( $template ) {
		return dirname(__FILE__) . '/template.php';
	}


?>
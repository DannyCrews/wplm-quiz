<?php

/**
 * Plugin Name:       WPLM Quiz
 * Plugin URI:        https:www.bu.edu
 * Description:       Create Multiple Choice Questions and Quizzes
 * Version:           0.1.1
 * Author:            Dan Crews
 * Author URI:        https://github.com/dannycrews
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wplm-quiz
 * Domain Path:       /languages
 */

class wplm_quiz {

  public $plugin_url;

  public function __construct() {

    $this->plugin_url = plugin_dir_url( __FILE__ );

    add_action( 'init', array( $this, 'wplm_add_custom_post_type' ) );
    add_action( 'init', array( $this,'wplm_create_taxonomies' ), 0 );
    add_action( 'add_meta_boxes', array( $this,'wplm_quiz_meta_boxes' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'wplm_admin_scripts' ) );
    add_action( 'save_post', array( $this, 'wplm_save_quizes' ) );
    add_action( 'admin_menu', array( $this, 'wplm_plugin_settings' ) );
    add_shortcode( 'wplm_show_quiz', array( $this, 'wplm_show_quiz' ) );

  }

  public function wplm_add_custom_post_type() {

    $labels = array(
      'name' => _x( 'Questions', 'wplm_quiz' ),
      'menu_name' => _x( 'WordPress Learning Module', 'wplm_quiz' ),
      'add_new' => _x( 'Add New ', 'wplm_quiz' ),
      'add_new_item' => _x( 'Add New Question', 'wplm_quiz' ),
      'new_item' => _x( 'New Question', 'wplm_quiz' ),
      'all_items' => _x( 'All Questions', 'wplm_quiz' ),
      'edit_item' => _x( 'Edit Question', 'wplm_quiz' ),
      'view_item' => _x( 'View Question', 'wplm_quiz' ),
      'search_items' => _x( 'Search Questions', 'wplm_quiz' ),
      'not_found' => _x( 'No Questions Found', 'wplm_quiz' ),
  );

  $args = array(
      'labels' => $labels,
      'hierarchical' => true,
      'description' => 'WP Learning Modules Quiz',
      'supports' => array( 'title', 'editor' ),
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

  register_post_type( 'wplm_quiz', $args );
  }

  function wplm_create_taxonomies() {

      register_taxonomy(
          'quiz_categories',
          'wplm_quiz',
          array(
              'labels' => array(
                  'name' => 'Quiz Category',
                  'add_new_item' => 'Add New Quiz Category',
                  'new_item_name' => "New Quiz Category"
              ),
              'show_ui' => true,
              'show_tagcloud' => false,
              'hierarchical' => true
          )
      );

  }

  function wplm_quiz_meta_boxes() {
      add_meta_box( 'quiz-answers-info', 'Quiz Answers Info', array( $this, 'wplm_quiz_answers_info' ), 'wplm_quiz', 'normal', 'high' );
  }

  function wplm_quiz_answers_info() {

      global $post;

      $question_answers = get_post_meta( $post->ID, '_question_answers', true );

      $question_answers = ( $question_answers == '' ) ? array( '', '', '', '', '' ) : json_decode( $question_answers );

      $question_correct_answer = trim( get_post_meta( $post->ID, '_question_correct_answer', true ) );

      $html = '<input type="hidden" name="question_box_nonce" value="' . wp_create_nonce( basename( __FILE__ ) ) . '" />';

      $html .= '<table class="form-table">';

      $html .= '<tr><th style=""><label for="Price">Correct Answer</label></th><td><select class="widefat" name="correct_answer" id="correct_answer" >';

      for ( $i = 1; $i <= 5; $i++ ) {

          if ( $question_correct_answer == $i ) {
              $html .= '<option value="' . $i . '" selected >Answer ' . $i . '</option>';
          }
          else {
              $html .= '<option value="' . $i . '">Answer ' . $i . '</option>';
          }

      }

      $html .= '</select></td></tr>';

      $index = 1;

      foreach ( $question_answers as $question_answer ) {

          $html .= '<tr><th style=""><label for="Price">Answer ' . $index . '</label></th>';
          $html .= '<td><textarea class="widefat" name="quiz_answer[]" id="quiz_answer' . $index . '" >' . esc_textarea( trim( $question_answer ) ) . '</textarea></td></tr>';

          $index++;

      }

      $html .= '</tr>';

      $html .= '</table>';

      echo $html;
  }

  function wplm_admin_scripts() {

      wp_register_script( 'quiz-admin', plugins_url( 'js/quiz.js', __FILE__ ), array( 'jquery' ) );

      wp_enqueue_script( 'quiz-admin' );

  }

  function wplm_save_quizes( $post_id ) {

      if ( ! wp_verify_nonce( $_POST['question_box_nonce'], basename( __FILE__ ) ) ) {

          return $post_id;

      }

      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

          return $post_id;

      }

      if ( 'wplm_quiz' == $_POST['post_type'] && current_user_can( 'edit_post', $post_id ) ) {

          $question_answers = isset( $_POST['quiz_answer'] ) ? ( $_POST['quiz_answer'] ) : array();

          $filtered_answers = array();

          foreach ( $question_answers as $answer ) {

              array_push( $filtered_answers, sanitize_text_field( trim( $answer ) ) );

          }

          $question_answers = json_encode( $filtered_answers );

          $correct_answer = isset( $_POST['correct_answer'] ) ? sanitize_text_field( $_POST['correct_answer'] ) : "";

          update_post_meta( $post_id, "_question_answers", $question_answers );

          update_post_meta( $post_id, "_question_correct_answer", $correct_answer );

      }
      else {

          return $post_id;

      }
  }

  function wplm_plugin_settings() {

      //create new top-level menu

      add_menu_page( 'WPLM Quiz Settings', 'WPLM Quiz Settings', 'administrator', 'quiz_settings', array( $this, 'wplm_display_settings' ) );

  }

  function wplm_display_settings() {

      $html = '<div class="wrap">
          <form method="post" name="options" action="options.php">
              <h2>Select Your Settings</h2>' . wp_nonce_field( 'update-options' ) . '

              <table width="100%" cellpadding="10" class="form-table">
                  <tr>
                      <td align="left" scope="row">
                          <label>Number of Questions</label><input type="text" name="wplm_num_questions" value="' . get_option( 'wplm_num_questions' ) . '" />
                      </td>
                  </tr>
                  <tr>
                      <td align="left" scope="row">
                          <label>Duration (Mins)</label><input type="text" name="wplm_duration" value="' . get_option( 'wplm_duration' ) . '" />
                      </td>
                  </tr>
              </table>

              <p class="submit">
                  <input type="hidden" name="action" value="update" />
                  <input type="hidden" name="page_options" value="wplm_num_questions,wplm_duration" />
                  <input type="submit" name="Submit" value="Update" />
              </p>

          </form>
      </div>';

      echo $html;
  }

  function wplm_show_quiz( $atts ) {

    global $post;

    $html = '<div id="quiz_panel"><form action="" method="POST">';

    $html .= '<div class="toolbar">';

    $html .= '<div class="toolbar_item"><select name="quiz_category" id="quiz_category">';

    // Retrive the quiz categories from database

    $quiz_categories = get_terms( 'quiz_categories', 'hide_empty=1' );

    foreach ( $quiz_categories as $quiz_category ) {

        $html .= '<option value="' . $quiz_category->term_id . '">' . $quiz_category->name . '</option>';

    }

    $html .= '</select></div>';

    $html .= '<input type="hidden" value="select_quiz_cat" name="wplm_action" />';

    $html .= '<div class="toolbar_item"><input type="submit" value="Select Quiz Category" /></div>';

    $html .= '</form>';

    $html .= '<div class="complete toolbar_item" ><input type="button" id="completeQuiz" value="Get Results" /></div>';

    // Implementation of Form Submission

    // Displaying the Quiz as unorderd list

    return $html;

}

}

$quiz = new wplm_quiz();

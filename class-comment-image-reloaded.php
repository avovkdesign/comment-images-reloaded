<?php


require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');


class Comment_Image_Reloaded {

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/
	 
	 
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugn option 
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      array
	 */
	private static $options = array();

	/**
	 * The maximum size of the file in bytes.
	 *
	 * @since    1.17.0
	 * @access   private
	 * @var      int
	 */
    private $limit_file_size;

	/**
	 * The maximum width for thumbnail images
	 *
	 * @since    1.18.0
	 * @access   private
	 * @var      int
	 */
    private $thumb_width;


	/**
	 * Whether or not the image needs to be approved before displaying
	 * it to the user.
	 *
	 * @since    1.17.0
	 * @access   private
	 * @var      bool
	 */
    private $needs_to_approve;


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		} // end if

		return self::$instance;

	} // end get_instance


	/**
	 * Initializes the plugin by setting localization, admin styles, and content filters.
	 */
	private function __construct() {

		// get plugin options
		self::$options = get_option( 'CI_reloaded_settings' );

		// Load plugin textdomain
		add_action( 'init', array( $this, 'plugin_textdomain' ) );


		// Determine if the hosting environment can save files.
		if( $this->can_save_files() ) {

			// check html5 comments support
			$themesupport = get_theme_support('html5');
		
			// add fix for xhtml comments
			if ( $themesupport === false || !in_array( 'comment-list', $themesupport ) ) {
 				add_action('comment_text', array( $this, 'get_html5_comment_content' ) );
			} 

			// Go ahead and enable comment images site wide
			add_option( 'comment_image_reloaded_toggle_state', 'enabled' );

			// Add comment related stylesheets and JavaScript
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
			add_action( 'wp_head', array( $this, 'add_authorslink_style' ) );

			add_action( 'wp_ajax_convert_img', array( $this, 'convert_images') );
			add_action( 'wp_ajax_cir_delete_image', array( $this, 'cir_delete_image') );

			// Add the Upload input to the comment form
			// add_action( 'comment_form_default_fields' , array( $this, 'add_image_upload_form' ) );
			add_action( 'comment_form' , array( $this, 'add_image_upload_form' ) );
			add_filter( 'wp_insert_comment', array( $this, 'save_comment_image' ) );
			add_filter( 'comments_array', array( $this, 'display_comment_image' ) );

			// clean commentmeta when comments or media image deleted
			add_filter( 'delete_comment', array( $this, 'clear_commentmeta_ondelete_comment' ) );
			add_filter( 'delete_attachment', array( $this, 'clear_commentmeta_ondelete_attachment' ) );

			// Add a note to recent comments that they have Comment Images
			add_filter( 'comment_row_actions', array( $this, 'recent_comment_has_image' ), 20, 2 );

			// Add a column to the Post editor indicating if there are Comment Images
			add_filter( 'manage_posts_columns', array( $this, 'post_has_comment_images' ) );
			add_filter( 'manage_posts_custom_column', array( $this, 'post_comment_images' ), 20, 2 );

			// Add a column to the comment images if there is an image for the given comment
			add_filter( 'manage_edit-comments_columns', array( $this, 'comment_has_image' ) );
			add_filter( 'manage_comments_custom_column', array( $this, 'comment_image' ), 20, 2 );

			// Setup the Project Completion metabox
			add_action( 'add_meta_boxes', array( $this, 'add_comment_image_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_comment_image_display' ) );


			add_action( 'admin_init', array( $this, 'CI_reloaded_settings_init') );
			add_action( 'admin_menu', array( $this, 'CI_reloaded_add_admin_menu') );

			// set maximum allowed file size get php.ini settings / CIR option / default 5MB
            $phpini_limit = self::getMaxFilesize(); // in bytes
            $opt = ( isset(self::$options['max_filesize']) ) ? self::$options['max_filesize'] : 5; // in MBytes
            $limit = min( $phpini_limit, self::MBtoB($opt) ); // set limit
            $this->limit_file_size = $limit; 

            // TODO make this value ajustable by site admin (on plugin settings page)
            $this->needs_to_approve = FALSE;

		} else {

			// If not, display a notice.
			add_action( 'admin_notices', array( $this, 'save_error_notice' ) );

		} // end if/else

	} // end constructor



	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/

	 /**
	  * Adds a column to the 'All Posts' page indicating whether or not there are
	  * Comment Images available for this post.
	  *
	  * @param	array	$cols	The columns displayed on the page.
	  * @param	array	$cols	The updated array of columns.
	  * @since	1.8
	  */
	public function post_has_comment_images( $cols ) {

		$cols['comment-image-reloaded'] = __( 'Comment Images', 'comment-images' );

		return $cols;

	} // end post_has_comment_images


	/**
	 * This function sets the comments_array working fine
	 *
	 * @param 	string 	$ctext
	 * @return 	string 	comment text with comment images
	 *
	 */
	function get_html5_comment_content( $ctext ){  // 

		return get_comment_text();

	}


	 /**
	  * Provides a link to the specified post's comments page if the post has comments that contain
	  * images.
	  *
	  * @param	string	$column_name	The name of the column being rendered.
	  * @param	int		$int			The ID of the post being rendered.
	  * @since	1.8
	  */
	 public function post_comment_images( $column_name, $post_id ) {

		 if( 'comment-image-reloaded' == strtolower( $column_name ) ) {

		 	// Get the comments for the current post.
		 	$args = array(
		 		'post_id' => $post_id
		 	);
		 	$comments = get_comments( $args );

		 	// Look at each of the comments to determine if there's at least one comment image
		 	$has_comment_image = false;
		 	foreach( $comments as $comment ) {

			 	// If the comment meta indicates there's a comment image and we've not yet indicated that it does...
			 	if( 0 != get_comment_meta( $comment->comment_ID, 'comment_image_reloaded', true ) && ! $has_comment_image ) {

			 		// ..Make a note in the column and link them to the media for that post
					$html = '<a href="edit-comments.php?p=' . $comment->comment_post_ID . '">';
						$html .= __( 'View Post Comment Images', 'comment-images' );
					$html .= '</a>';

			 		echo $html;

			 		// Mark that we've discovered at least one comment image
			 		$has_comment_image = true;

			 	} // end if

		 	} // end foreach

		 } // end if

	 } // end post_comment_images


	 /**
	  * Adds a column to the 'Comments' page indicating whether or not there are
	  * Comment Images available.
	  *
	  * @param	array	$columns	The columns displayed on the page.
	  * @param	array	$columns	The updated array of columns.
	  */
	 public function comment_has_image( $columns ) {

		 $columns['comment-image-reloaded'] = __( 'Comment Image', 'comment-images' );

		 return $columns;

	 } // end comment_has_image


	 /**
	  * Renders the actual image for the comment.
	  *
	  * @param	string	The name of the column being rendered.
	  * @param	int		The ID of the comment being rendered.
	  * @since	1.8
	  */
	 public function comment_image( $column_name, $comment_id ) {

		 if( 'comment-image-reloaded' == strtolower( $column_name ) ) {

			 if( 0 != ( $comment_image_data = get_comment_meta( $comment_id, 'comment_image_reloaded', true ) ) ) {

			 	$image_attributes = wp_get_attachment_image_src( $comment_image_data );
				$image_url = $image_attributes[0];
				$html = '<img src="' . $image_url . '" width="150" style="max-width:100%"/>';
				$html .= '<div class="row-actions">';
				$html .= '<button class="button delete-cid" data-cid=' . $comment_id . '" data-aid="'. $comment_image_data .'">';
				$html .= __( 'Delete image', 'comment-images' );
				$html .= '</button>';
				$html .= '</div>';

				 echo $html;

	 		 } // end if

 		 } // end if/else

	 } // end comment_image


	 /**
	  * Determines whether or not the current comment has comment images. If so, adds a new link
	  * to the 'Recent Comments' dashboard.
	  *
	  * @param	array	$options	The array of options for each recent comment
	  * @param	object	$comment	The current recent comment
	  * @return	array	$options	The updated list of options
	  * @since	1.8
	  */
	 public function recent_comment_has_image( $options, $comment ) {

		 if( 0 != ( $comment_image = get_comment_meta( $comment->comment_ID, 'comment_image_reloaded', true ) ) ) {

			 $html = '<a href="edit-comments.php?p=' . $comment->comment_post_ID . '">';
			 	$html .= __( 'Comment Images', 'comment-images' );
			 $html .= '</a>';

			 $options['comment-images'] = $html;

		 } // end if

		 return $options;

	 } // end recent_comment_has_image



	 /**
	  * Loads the plugin text domain for translation
	  */
	 function plugin_textdomain() {
		 load_plugin_textdomain( 'comment-images', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
	 } // end plugin_textdomain

	 /**
	  * In previous versions of the plugin, the image were written out after the comments. Now,
	  * they are actually part of the comment content so we need to update all old options.
	  *
	  * Note that this option is not removed on deactivation because it will run *again* if the
	  * user ever re-activates it this duplicating the image.
	  */
	

	/**
	 * Import images meta from Comment Image
	 *
	 */
	public static function convert_images() {
	 	
	 	$counter = 0;
	 	$attachments = array();

		$old_meta_key = 'comment_image';
		$new_meta_key = 'comment_image_reloaded';

		// get all comments with Comment Image meta key
		$comments = get_comments( 'meta_key=' . $old_meta_key );


		/**
		 *
		 * Iterate through each of the comments...
		 *
		 */
 		foreach( $comments as $comment ) {

			// Get the associated comment image
			$comment_image = get_comment_meta( $comment->comment_ID, $old_meta_key, true );

			$image_path = $comment_image['file'];


			/**
			 * check absolute FILE path not exists
			 * 
			 */
			if ( !file_exists( $image_path ) ) {
				
				$pos_path = strpos( $image_path, 'wp-content' );
				$fixed_image_path = ABSPATH . substr( $image_path, $pos );

				// try fix image path
				if ( file_exists($fixed_image_path) ) {
					$image_path = $fixed_image_path;
				} 

				// check path by url
				else {

					$pos_url = strpos( $comment_image['url'], 'wp-content' );
					$fixed_url_path = ABSPATH . substr( $comment_image['url'], $pos_url );

					// try fix image path
					if ( file_exists( $fixed_url_path ) ) {
						$image_path = $fixed_url_path;
					} else {
						continue;
					}

				}

			} // end !file_exists


			// get post ID
			$post_id = $comment->comment_post_ID;

			// save attachments data if it not exists
			if ( !array_key_exists( $post_id, $attachments) ) {
				$new = get_attached_media( 'image', $post_id );
				$attachments[ $post_id ] = json_decode( json_encode($new), true ); // save WP_Post as associative array
			}

			// try get exists image
			$imageID_in_medialibrary = 0;
			foreach ( $attachments[ $post_id ] as $att_id => $att ) {
				if ( $comment_image['url'] === $att['guid'] ) {
					$imageID_in_medialibrary = $att['ID'];
				}
			}

			// update meta key if attachment exist
			if ( $imageID_in_medialibrary != 0 ) {

				update_comment_meta( $comment->comment_ID, $new_meta_key, $imageID_in_medialibrary );
				$counter++;

			} else {				

				// upload new image attachment
				// ??? (((
				$file = array(
					'name'     => basename( $image_path ),
					'type'     => $comment_image['type'],
					'size'     => filesize( $image_path ),
					'tmp_name' => $image_path,
					'error'    => $comment_image['error'],
				);

				$id = media_handle_sideload( $file, $comment->comment_post_ID );

				if( !is_wp_error($id)  ){
					update_comment_meta( $comment->comment_ID, $new_meta_key, $id );
					$counter++;
				}

				//@unlink( $file['tmp_name'] );

			}

		} // end foreach

		$response = __('Updated ','comment-images') . $counter .' '. self::num_word($counter) . $dump;

		echo $response;
		wp_die();

	 } // end update_old_comments



	 /**
	  * Display a WordPress error to the administrator if the hosting environment does not support 'file_get_contents.'
	  */
	 function save_error_notice() {

		 $html = '<div id="comment-image-notice" class="error">';
		 	$html .= '<p>';
		 		$html .= __( '<strong>Comment Images Notice:</strong> Unfortunately, your host does not allow uploads from the comment form. This plugin will not work for your host.', 'comment-images' );
		 	$html .= '</p>';
		 $html .= '</div><!-- /#comment-image-notice -->';

		 echo $html;

	 } // end save_error_notice

	
	/**
	 *  add small css for author's link
	 */
	function add_authorslink_style() {
		
		if ( !isset(self::$options['show_brand_img']) || empty(self::$options['show_brand_img']) ){
			echo "<style>.cir-link{height:30px;display:inline-block;width:117px;overflow:hidden;}.cir-link,.cir-link img{padding:0;margin:0;border:0}.cir-link:hover img{    position:relative;bottom:30px}</style>\n";
		}
	}


	/**
	 * Adds the public JavaScript to the single post page.
	 */
	function add_scripts() {

		if ( is_single() || is_page() ) {

			$jsfile = 'js/cir-withoutzoom.min.js';

			if ( isset(self::$options['image_zoom']) && 'enable' == self::$options['image_zoom'] ) {
				$jsfile = 'js/cir-withzoom.min.js';
				wp_enqueue_style( 'magnific', plugins_url( 'js/magnific.css', __FILE__) );
			}

			wp_register_script( 'comment-images-reloaded', plugins_url( $jsfile, __FILE__ ), array( 'jquery' ), false, true );
            wp_localize_script(
            	'comment-images-reloaded',
            	'cm_imgs',
            	array(
                	'fileTypeError' => __( '<strong>Heads up!</strong> You are attempting to upload an invalid image. If saved, this image will not display with your comment.', 'comment-images' ),
					'fileSizeError' => __( '<strong>Heads up!</strong> You are attempting to upload an image that is too large. If saved, this image will not be uploaded.<br />The maximum file size is: ', 'comment-images' ),
					'limitFileSize' => $this->limit_file_size
				)
			);
			wp_enqueue_script( 'comment-images-reloaded' );

		} // end if

	} // end add_scripts


	/**
	 * Adds the public JavaScript to the single post editor
	 */
	function add_admin_scripts() {

		wp_register_script( 'comment-images-reloaded-ajax', plugins_url( 'js/admin-ajax.min.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 
			'comment-images-reloaded-ajax', 
			'cmr_reloaded_ajax_object', 
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'before_delete_text' => __( 'Do you want to permanently delete an image attached to this comment?', 'comment-images' ),
				'after_delete_text' => __( 'Image deleted!', 'comment-images' ),
			) 
		);
		wp_enqueue_script( 'comment-images-reloaded-ajax' );

		$screen = get_current_screen();
		if( 'post' === $screen->id || 'page' == $screen->id ) {

			wp_register_script( 'comment-images-reloaded-admin', plugins_url( 'js/admin.min.js', __FILE__ ), array( 'jquery' ) );
			
            wp_localize_script(
            	'comment-images-reloaded-admin',
            	'cm_imgs',
            	array(
                	'toggleConfirm' => __( 'By doing this, you will toggle Comment Images for all posts on your blog. Are you sure you want to do this?', 'comment-images' )
				)
			);

			wp_enqueue_script( 'comment-images-reloaded-admin' );
			

		} // end if

	} // end add_admin_scripts



	/**
	 * Adds the comment image upload form to the comment form.
	 *
	 * @param	$post_id	The ID of the post on which the comment is being added.
	 */
 	function add_image_upload_form( $post_id ) {
 	// function add_image_upload_form( $fields ) {

 		$current_post_state = get_post_meta( $post_id, 'comment_images_reloaded_toggle', true ) 
 			? get_post_meta( $post_id, 'comment_images_reloaded_toggle', true ) 
 			: 'enable';

		$option = get_option( 'CI_reloaded_settings' );
		// $option

		$all_posts_state = !empty($option['disable_comment_images']) 
			? $option['disable_comment_images'] 
			: '';

		$logoimg = '<img src="data:image/png;base64,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" alt="wp-puzzle.com logo">';

		$brand_img = ( !empty($option['show_brand_img']) && 'disable'==$option['show_brand_img'] )
			? ''
			: '<a href="http://wp-puzzle.com/" rel="external nofollow" target="_blank" class="cir-link">'. $logoimg .'</a>';

		// Create the label and the input field for uploading an image
	 	if( 'disable' != $all_posts_state  && $current_post_state == 'enable' ){

		 	$html = '<div id="comment-image-reloaded-wrapper">';
				$html .= '<p id="comment-image-reloaded-error"></p>';
				$html .= "<label for='comment_image_reloaded_$post_id'>". $option['before_title'] ."</label>";
				$html .= "<p class='comment-image-reloaded'><input type='file' name='comment_image_reloaded_$post_id' id='comment_image_reloaded' /></p>";
				$html .= $brand_img;
			 $html .= '</div><!-- #comment-image-wrapper -->';

			 echo $html;
			 // $fields['comment_notes_after'] = $html;


		 } // end if

		 // return $fields;

	} // end add_image_upload_form



	/**
	 * Adds the comment image upload form to the comment form.
	 *
	 * @param	$comment_id	The ID of the comment to which we're adding the image.
	 */
	function save_comment_image( $comment_id ) {

		// The ID of the post on which this comment is being made
		$post_id = $_POST['comment_post_ID'];

		// The key ID of the comment image
		$comment_image_id = "comment_image_reloaded_$post_id";

		// If the nonce is valid and the user uploaded an image, let's upload it to the server
		// if( isset( $_FILES[ $comment_image_id ] ) && ! empty( $_FILES[ $comment_image_id ] ) ) {
		if( isset( $_FILES[ $comment_image_id ] ) && !empty( $_FILES[ $comment_image_id ]['name'] )  ) {
// print_r($_FILES);
// print_r($this->limit_file_size);
            // disable save files larger than $limit_filesize
            if ( $this->limit_file_size < $_FILES[ $comment_image_id ]['size'] ) {

                echo __( "Error: Uploaded file is too large. <br/> Go back to: ", 'comment-images' );
                echo '<a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a>';
                die;

            }

            // check errors 
            if ( !empty( $_FILES[ $comment_image_id ]['error'] ) ) {

                echo __( "Unknown error occurred while loading image.<br/> Go back to: ", 'comment-images' );
                echo '<a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a>';
                die;

            }

            // safe image name
            $safe_name = preg_replace("/[^A-Za-z0-9_\-\.]/", '', $_FILES[ $comment_image_id ]['name'] );

            // if is empty name - add same random digits
            $onlyname = substr( $safe_name, 0, -4 );
            if ( empty($onlyname) ) {
            	$safe_name = $comment_image_id . rand( 100, 900 ) . $safe_name;
            }

			// Store the parts of the file name into an array
			// $file_name_parts = explode( '.', $_FILES[ $comment_image_id ]['name'] );
			$file_name_parts = explode( '.', $_FILES[ $comment_image_id ]['name'] );

            // Get file ext.
            $file_ext = $file_name_parts[ count( $file_name_parts ) - 1 ];
			
			// If the file is valid, upload the image, and store the path in the comment meta
			if( $this->is_valid_file_type( $file_ext ) ) {

				// Upload the comment image to the uploads directory
				$comment_image_file = wp_upload_bits( $comment_id . '.' . $file_ext, null, file_get_contents( $_FILES[ $comment_image_id ]['tmp_name'] ) );
				
				$img = $_FILES[ $comment_image_id ];
				$img['name'] = $safe_name;
				// $id = media_handle_sideload( $_FILES[ $comment_image_id ], $post_id);
				$id = media_handle_sideload( $img, $post_id);

				// Set post meta about this image. Need the comment ID and need the path.
				if( FALSE === $comment_image_file['error'] ) {

					// Since we've already added the key for this, we'll just update it with the file.
					add_comment_meta( $comment_id, 'comment_image_reloaded', $id );

				} // end if/else

                // Send comment to approval if this option checked by admin
                if ( TRUE === $this->needs_to_approve ) {

                    $commentarr = array();
                    $commentarr['comment_ID'] = $comment_id;
                    $commentarr['comment_approved'] = 0;

                    wp_update_comment( $commentarr );

                }

			} // end if

		} // end if

	} // end save_comment_image



	/**
	 * Appends the image below the content of the comment.
	 *
	 * @param	$comment	The content of the comment.
	 */
	function display_comment_image( $comments ) {

		if( count( $comments ) < 1){
			return $comments;
		}

		global $wpdb,$post;

		$comment_ids = '';
		$current_post_state = get_post_meta( $post->ID, 'comment_images_reloaded_toggle', true );
		$option = get_option( 'CI_reloaded_settings' );

		// get current file size or set default to 'large'
		$size = $option['image_size'] 
			? $option['image_size'] 
			: 'large';

		$all_posts_state = !empty($option['disable_comment_images']) 
			? $option['disable_comment_images'] 
			: '';

		// get comments ID list
		foreach ($comments as $count => $comment) {
			$comment_ids .= $comment->comment_ID . ',';
		}
		$comment_ids =  rtrim ( $comment_ids, ',' );

		// get all meta fields for comments images
		$table = $wpdb->base_prefix . 'commentmeta';
		if( !empty($comment_ids) ){
			$fivesdrafts = $wpdb->get_results("SELECT comment_id, meta_value  FROM $table
												WHERE comment_id IN ($comment_ids) AND meta_key = 'comment_image_reloaded'
												ORDER BY meta_id ASC");

			$urls_from_db = $wpdb->get_results("SELECT comment_id, meta_value  FROM $table
												WHERE comment_id IN ($comment_ids) AND meta_key = 'comment_image_reloaded_url'
												ORDER BY meta_id ASC");
		}

		$metadata_ids = array();
		$metadata_url = array();

		foreach ($urls_from_db as $key => $value) {
			$metadata_url[$value->comment_id] = $value->meta_value;
		}

		foreach ($fivesdrafts as $key => $value) {
			$metadata_ids[$value->comment_id] = $value->meta_value;
		}
		

		//
		// Make sure that there are comments
		//
		if( count( $comments ) > 0 ) {

			// Loop through each comment...
			foreach( $comments as $comment ) {

				// ...and if the comment has a comment image...
				if( !empty($metadata_ids[$comment->comment_ID]) ) {

					// ...get the comment image meta
					//$comment_image = get_comment_meta( $comment->comment_ID, 'comment_image_reloaded', true );
					$img_url = '';
					$img_url_out = '';

					// Size of the image to show (thumbnail, large, full, medium)
					if ( array_key_exists($comment->comment_ID,$metadata_url) && !empty($metadata_url[$comment->comment_ID]) ){

						$img_url = unserialize($metadata_url[$comment->comment_ID]);
						if(!$img_url){
							foreach( get_intermediate_image_sizes() as $_size ){
								$img_url[$_size] = wp_get_attachment_image($metadata_ids[$comment->comment_ID], $_size);
							}
							$img_url['full'] = wp_get_attachment_image($metadata_ids[$comment->comment_ID], 'full');
							update_comment_meta($comment->comment_ID, 'comment_image_reloaded_url',$img_url);
						}
						if( !empty( $img_url[$size] ) )
							$img_url_out = $img_url[$size];
						else
							$img_url_out = $img_url['full'];

					} else {
						foreach( get_intermediate_image_sizes() as $_size ){
							$img_url[$_size] = wp_get_attachment_image($metadata_ids[$comment->comment_ID], $_size);
						}
						$img_url['full'] = wp_get_attachment_image($metadata_ids[$comment->comment_ID], 'full');
						add_comment_meta( $comment->comment_ID, 'comment_image_reloaded_url',$img_url);
						if( !empty( $img_url[$size] ) )
							$img_url_out = $img_url[$size];
						else
							$img_url_out = $img_url['full'];

					}
					// ...and render it in a paragraph element appended to the comment
					if ( isset(self::$options['image_zoom']) && 'enable' == self::$options['image_zoom'] ) {

						// get full image URI
						preg_match( '/src=[\'|\"]([^\'\"]*)/i', $img_url['full'], $matches );

						$comment->comment_content .= '<p class="comment-image-reloaded">';
							if ( $matches ) {
								$comment->comment_content .= '<a class="cir-image-link" href="'. $matches[1] .'">'. $img_url_out . '</a>';
							} else {
								$comment->comment_content .= $img_url_out;
							}
						$comment->comment_content .= '</p><!-- /.comment-image-reloaded -->';

					} else {

						$comment->comment_content .= '<p class="comment-image-reloaded">';
							$comment->comment_content .= $img_url_out;
						$comment->comment_content .= '</p><!-- /.comment-image-reloaded -->';

					}

				} // end if

			} // end foreach

		} // end if

		return $comments;

	} // end display_comment_image


	//
	// clear commentmeta on delete COMMENT
	//
	function clear_commentmeta_ondelete_comment( $comment_id ) {

		$attachment_id = get_comment_meta( $comment_id, 'comment_image_reloaded', true );

		wp_delete_attachment( intval($attachment_id) );
		
		delete_comment_meta( $comment_id, 'comment_image_reloaded' );
		delete_comment_meta( $comment_id, 'comment_image_reloaded_url' );
		
	}

	//
	// clear commentmeta on delete ATTACHMENT
	//
	function clear_commentmeta_ondelete_attachment( $id ) {

		global $wpdb;

		// $table = $wpdb->base_prefix . 'commentmeta';
		// $postids = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM $table WHERE meta_value = $id" ) );
		$postids = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_value = %d", $id ) );
		// $postids = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_value = $id" ) );

		foreach ( $postids as $cid ) {
			delete_comment_meta( $cid, 'comment_image_reloaded' );
			delete_comment_meta( $cid, 'comment_image_reloaded_url' );
		}
		
	}



	/**
	 * delete images
	 *
	 */
	public static function cir_delete_image() {

		if ( !isset($_POST['cid']) || !isset($_POST['aid']) ) {
			echo 'false';
			die;
		}

		$aid = $_POST['aid'];
		$cid = $_POST['cid'];

		$deleted = wp_delete_attachment( intval($aid) );
		
		if ( $deleted ) {

			delete_comment_meta( $cid, 'comment_image_reloaded' );
			delete_comment_meta( $cid, 'comment_image_reloaded_url' );		

			echo 'true';
		}

		die;

	}



	/*--------------------------------------------*
	 * Meta Box Functions
	 *---------------------------------------------*/

	 /**
	  * Registers the meta box for displaying the 'Comment Images' options in the post editor.
	  *
	  * @version	1.0
	  * @since 		1.8
	  */
	 public function add_comment_image_meta_box() {

		 add_meta_box(
		 	'disable_comment_images_reloaded',
		 	__( 'Comment Images', 'comment-images' ),
		 	array( $this, 'comment_images_display' ),
		 	'post',
		 	'side',
		 	'low'
		 );

		 add_meta_box(
		 	'disable_comment_images_reloaded',
		 	__( 'Comment Images', 'comment-images' ),
		 	array( $this, 'comment_images_display' ),
		 	'page',
		 	'side',
		 	'low'
		 );

	 } // end add_project_completion_meta_box


	 /**
	  * Displays the option for disabling the Comment Images upload field.
	  *
	  * @version	1.0
	  * @since 		1.8
	  */
	 public function comment_images_display( $post ) {

		 wp_nonce_field( plugin_basename( __FILE__ ), 'comment_images_reloaded_display_nonce' );

		 $html = '<p class="comment-image-info" style="text-align:center;color: #3a87ad;margin: 10px 0 10px 0;padding:10px;background-color: #d9edf7;border-left: 5px solid #3a87ad;">' . __( 'Doing this will only update <strong>this</strong> post.', 'comment-images' ) . '</p>';
		 $html .= '<select name="comment_images_reloaded_toggle" id="comment_images_reloaded_toggle" class="comment_images_reloaded_toggle_select" style="width:100%;">';
		 	$html .= '<option value="enable" ' . selected( 'enable', get_post_meta( $post->ID, 'comment_images_reloaded_toggle', true ), false ) . '>' . __( 'Enable comment images for this post.', 'comment-images' ) . '</option>';
		 	$html .= '<option value="disable" ' . selected( 'disable', get_post_meta( $post->ID, 'comment_images_reloaded_toggle', true ), false ) . '>' . __( 'Disable comment images for this post.', 'comment-images' ) . '</option>';
		 $html .= '</select>';

		 $html .= '<hr />';

		 echo $html;

	 } // end comment_images_display



	 /**
	  * Saves the meta data for displaying the 'Comment Images' options in the post editor.
	  *
	  * @version	1.0
	  * @since 		1.8
	  */
	 public function save_comment_image_display( $post_id ) {

		 // If the user has permission to save the meta data...
		 if( $this->user_can_save( $post_id, 'comment_images_reloaded_display_nonce' ) ) {

			// Only do this if the source of the request is from the button
			if( isset( $_POST['comment_image_reloaded_source'] ) && 'button' == $_POST['comment_image_reloaded_source'] ) {

				if( '' == get_option( 'comment_image_reloaded_toggle_state' ) || 'enabled' == get_option( 'comment_image_reloaded_toggle_state' ) ) {

					
					update_option( 'comment_image_reloaded_toggle_state', 'disabled' );

				} elseif ( 'disabled' == get_option( 'comment_image_reloaded_toggle_state' ) ) {

					
					update_option( 'comment_image_reloaded_toggle_state', 'enabled' );

				} // end if

			// Otherwise, we're doing this for the post-by-post basis with the select box
			} else {

			 	// Delete any existing meta data for the owner
				if( get_post_meta( $post_id, 'comment_images_reloaded_toggle' ) ) {
					delete_post_meta( $post_id, 'comment_images_reloaded_toggle' );
				} // end if
				update_post_meta( $post_id, 'comment_images_reloaded_toggle', $_POST[ 'comment_images_reloaded_toggle' ] );

			} // end if/else

		 } // end if

	 } // end save_comment_image_display



	/*--------------------------------------------*
	 * Utility Functions
	 *--------------------------------------------*/

	/**
	 * Determines if the specified type if a valid file type to be uploaded.
	 *
	 * @param	$type	The file type attempting to be uploaded.
	 * @return			Whether or not the specified file type is able to be uploaded.
	 */
	private function is_valid_file_type( $type ) {

		$type = strtolower( trim ( $type ) );
		return 	$type == 'png' ||
				$type == 'gif' ||
				$type == 'jpg' ||
				$type == 'jpeg';

	} // end is_valid_file_type


	/**
	 * Determines if the hosting environment allows the users to upload files.
	 *
	 * @return			Whether or not the hosting environment supports the ability to upload files.
	 */
	private function can_save_files() {
		return function_exists( 'file_get_contents' );
	} // end can_save_files


	//
	//
	//
	private static function num_word($num){
		$words = array(__('image','comment-images'),__('images','comment-images'),__('images.','comment-images'));
    	$num = $num % 100;
    		if ($num > 19) {
    			$num = $num % 10;
    		}
    	switch ($num) {
    		case 1: {
    		return($words[0]);
    		}
    		case 2: case 3: case 4: {
    		return($words[1]);
    		}
    		default: {
    		return($words[2]);
    		}
    	}
    }

	
	 /**
	  * Determines whether or not the current user has the ability to save meta data associated with this post.
	  *
	  * @param		int		$post_id	The ID of the post being save
	  * @param		bool				Whether or not the user has the ability to save this post.
	  * @version	1.0
	  * @since		1.8
	  */
	 private function user_can_save( $post_id, $nonce ) {

	    $is_autosave = wp_is_post_autosave( $post_id );
	    $is_revision = wp_is_post_revision( $post_id );
	    $is_valid_nonce = ( isset( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], plugin_basename( __FILE__ ) ) ) ? true : false;

	    // Return true if the user is able to save; otherwise, false.
	    return ! ( $is_autosave || $is_revision) && $is_valid_nonce;

	 } // end user_can_save


	//
	//
	//
	public static function CI_reloaded_add_admin_menu(  ) { 
		add_options_page( 
			'Comment Images Reloaded', 
			'Comment Images Reloaded', 
			'manage_options', 
			'comment_images_reloaded', 
			array( 'Comment_Image_Reloaded', 'CI_reloaded_options_page')
		);
	}

	//
	//
	//
	public static function CI_reloaded_options_page(  ) { 
		echo "<div class='wrap'>
				<div class='updated settings-error notice is-dismissible' style='display:none'>
					<pre class='responce_convert'></pre>
				<div class='notice-dismiss'>
			</div></div>";
		echo "<form action='options.php' method='post'>";
		echo "<h1>Comment Images Reloaded</h1>";
		settings_fields( 'CI_reloaded_settings_page' );
		do_settings_sections( 'CI_reloaded_settings_page' );
		submit_button();
		echo "</form></div>";
	}

	//
	//
	//
	public static function CI_reloaded_settings_init(  ) { 

		register_setting( 'CI_reloaded_settings_page', 'CI_reloaded_settings' );

		//
		// import images
		//
		add_settings_section(
			'CIR_import', 
			__('Import from Comment Images', 'comment-images'),
			array( 'Comment_Image_Reloaded', 'CI_reloaded_settings_section_callback'), 
			'CI_reloaded_settings_page'
		);

		add_settings_field( 
			'convert_images', 
			__('Comment Images import','comment-images'), 
			array( 'Comment_Image_Reloaded', 'CI_reloaded_convert_images'), 
			'CI_reloaded_settings_page', 
			'CIR_import' 
		);

		//
		// other settings
		//
		add_settings_section(
			'CI_reloaded_checkbox_settings', 
			__('Settings Comment Images Reloaded', 'comment-images'),
			array( 'Comment_Image_Reloaded', 'CI_reloaded_settings_section_callback'), 
			'CI_reloaded_settings_page'
		);

		add_settings_field( 
			'image_size', 
			__('Image size', 'comment-images'),
			array( 'Comment_Image_Reloaded', 'CIR_imagesize_render'), 
			'CI_reloaded_settings_page', 
			'CI_reloaded_checkbox_settings' 
		);

		add_settings_field( 
			'max_filesize', 
			__('Maximum file size', 'comment-images'),
			array( 'Comment_Image_Reloaded', 'CIR_maxfilesize_render'), 
			'CI_reloaded_settings_page', 
			'CI_reloaded_checkbox_settings' 
		);

		add_settings_field( 
			'before_title', 
			__('Text before input', 'comment-images'),
			array( 'Comment_Image_Reloaded', 'CIR_beforetitle_render'), 
			'CI_reloaded_settings_page', 
			'CI_reloaded_checkbox_settings' 
		);
		
		add_settings_field( 
			'image_zoom', 
			__('Images zoom', 'comment-images'),
			array( 'Comment_Image_Reloaded', 'CI_imageszoom_render'), 
			'CI_reloaded_settings_page', 
			'CI_reloaded_checkbox_settings' 
		);		
		
		add_settings_field( 
			'show_brand_img', 
			__("Author's link", 'comment-images'),
			array( 'Comment_Image_Reloaded', 'CIR_show_brand_img'), 
			'CI_reloaded_settings_page', 
			'CI_reloaded_checkbox_settings' 
		);		

		add_settings_field( 
			'disable_comment_images', 
			__('Disable for all', 'comment-images'),
			array( 'Comment_Image_Reloaded', 'CI_disableCIR_render'), 
			'CI_reloaded_settings_page', 
			'CI_reloaded_checkbox_settings' 
		);



	}

	
	//
	// Render convert button
	//
	public static function CI_reloaded_convert_images(){
		$html = '<input type="button" class="button" id="convert_images" value="' . __('Import all images data','comment-images') . '">';
		$html .= '<p class="description">'. __('You can import data from original Comment Images plugin. This is leave all data without deleting anything','comment-images') .'</p>';
		echo $html;
	}

	//
	// Render image sizes
	//
	public static function CIR_imagesize_render() {

		$sizes = get_intermediate_image_sizes();
		$html = '';
		$all_sizes = array();
		global $_wp_additional_image_sizes;
		foreach($sizes as $size){
			if($size == 'medium_large') continue;
			
			if ( in_array( $size, array('thumbnail', 'medium', 'full', 'large') ) ) {
				$all_sizes[$size]['width']  = get_option( "{$size}_size_w" );
				$all_sizes[$size]['height'] = get_option( "{$size}_size_h" );
			} elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
				$all_sizes[$size] = array(
					'width'  => $_wp_additional_image_sizes[ $size ]['width'],
					'height' => $_wp_additional_image_sizes[ $size ]['height'],
				);
			}
			if($all_sizes[$size]['height'] != 0 && $all_sizes[$size]['width'] != 0){

			$html .= '<input type="radio" id="radio_'.$size.'" name="CI_reloaded_settings[image_size]" value="'.$size.'"' . checked( $size, self::$options['image_size'], false ) . '/>';
	    	$html .= '<label for="radio_'.$size.'">' . $size . ' ( '.$all_sizes[$size]['width'] . 'x' . $all_sizes[$size]['height'] . ' )</label><br>';
	    	}
		}


		$html .= '<input type="radio" id="radio_full" name="CI_reloaded_settings[image_size]" value="full"' . checked( 'full', self::$options['image_size'], false ) . '/>';
	    $html .= '<label for="radio_full">full ('.__('Original size of the image', 'comment-images').')</label><br>';

	 
	    $html .= '<script type="text/javascript"> 
	    			function my_alert(){ 
	    				return confirm("'. __('Converting all images from Comment Images to Comment Images Reloaded. Disable old plugin to avoid dublicating the images in comments. You can allways revert to old plugin','comment-images').'");}</script>';
	    echo $html;
	
	} 
	
 
	//
	// Render disable CIR new uploads
	//
	public static function CIR_maxfilesize_render(){

		$phpini_limit = self::BtoMB( self::getMaxFilesize() );

		$val = ( isset(self::$options['max_filesize']) ) 
			? min( $phpini_limit, self::$options['max_filesize'] )
			: min( $phpini_limit, 5 );

		echo '<label><input type="text" name="CI_reloaded_settings[max_filesize]" value="'. $val .'" /> MB</label> ';
		echo '<code>'. self::MBtoB( $val ) . __(' bytes', 'comment-images') . '</code>';
		echo '<p class="description">'. __('Maximum allowed file size ', 'comment-images') . $phpini_limit  . ' MB ('. __('php.ini settings', 'comment-images') .')</p>';

	}
	
 
	//
	// Render before title
	//
	public static function CIR_beforetitle_render(){

		$phpini_limit = self::BtoMB( self::getMaxFilesize() );

		$val = ( isset(self::$options['before_title']) ) 
			? self::$options['before_title']
			: __( 'Select an image for your comment (GIF, PNG, JPG, JPEG):', 'comment-images' );

		echo '<input type="text" name="CI_reloaded_settings[before_title]" class="regular-text" value="'. $val .'" />';
		echo '<p class="description">'. __('Enter custom title for file input field', 'comment-images') . '</p>';

	}

 
	//
	// Render images zoom
	//
	public static function CI_imageszoom_render(){
		$option = '';
		if( isset(self::$options['image_zoom']) ) {
			$option = self::$options['image_zoom'];
		} else {
			$option = 'disable'; // default zoom OFF
		}
		echo '<label><input type="checkbox" name="CI_reloaded_settings[image_zoom]" value="enable" ' .checked( "enable", $option, false ) .' /> ';
		echo __('Enable image zoom on click (it work with Magnific Popup jQuery plugin)', 'comment-images') . '</label>';
	}

 
 
	//
	// Render show brand img
	//
	public static function CIR_show_brand_img(){
		$option = '';
		if( isset(self::$options['show_brand_img']) ) {
			$option = self::$options['show_brand_img'];
		} else {
			$option = 'enable'; // default link ON
		}
		echo '<label><input type="checkbox" name="CI_reloaded_settings[show_brand_img]" value="disable" ' .checked( "disable", $option, false ) .' /> ';
		echo __( "Check it to hide author's link", 'comment-images') . '</label>';
		echo '<p class="description">' . __('We place a small link under the image field, letting others know about our plugin. Thanks for your promotion!', 'comment-images') . '</p>';
	}


 
	//
	// Render disable CIR new uploads
	//
	public static function CI_disableCIR_render(){
		$option = '';
		if( isset(self::$options['disable_comment_images']) ) {
			$option = self::$options['disable_comment_images'];
		} else {
			$option = 'enable'; // default it OFF
		}
		echo '<label><input type="checkbox" name="CI_reloaded_settings[disable_comment_images]" value="disable" ' .checked( "disable", $option, false ) .' /> ';
		echo __('Deactivate images for all posts', 'comment-images') . '</label>';
	}

	//
	//
	//
	public static function CI_reloaded_settings_section_callback(  ) { 

	}



	//
	// get max filesize (in bytes) allowed in php.ini
	//
	public static function getMaxFilesize() {

		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$max_size = self::parse_size(ini_get('post_max_size'));

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = self::parse_size( ini_get('upload_max_filesize') );
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}

		return $max_size;

	}



	/* ==================================================================================== */
	// filesize & php.ini
	/* ==================================================================================== */
	public static function BtoMB( $bytes ) {
		return round( $bytes / 1048576 , 2 );
	}
	public static function MBtoB( $MB ) {
		return round( $MB * 1048576 );
	}
	public static function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else {
			return round($size);
		}
	}


} // end class
	


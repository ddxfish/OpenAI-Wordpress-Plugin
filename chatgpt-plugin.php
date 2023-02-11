<?php
/**
* Plugin Name: ChatGPT OpenAI Plugin
* Plugin URI: https://github.com/ddxfish
* Description: ChatGPT integration using OpenAI API key. Creates shortcode [chatgpt-reader] [chatgpt-image]
* Version: 0.1
* Author: ddxfish
* Author URI: https://github.com/ddxfish
**/


function chatgpt_render_plugin_settings() {
    ?>
    <h2>ChatGPT Plugin Settings</h2>
    <form action="options.php" method="post">
        <?php 
        settings_fields( 'chatgpt_plugin_options' );
        do_settings_sections( 'chatgpt_plugin' ); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
    </form>
    <?php
}

function chatgpt_register_settings() {
    register_setting( 'chatgpt_plugin_options', 'chatgpt_plugin_options', 'chatgpt_plugin_options_validate' );
    add_settings_section( 'api_settings', 'API Settings', 'chatgpt_plugin_section_text', 'chatgpt_plugin' );
    add_settings_field( 'chatgpt_plugin_setting_api_key', 'API Key', 'chatgpt_plugin_setting_api_key', 'chatgpt_plugin', 'api_settings' );
}
add_action( 'admin_init', 'chatgpt_register_settings' );


function chatgpt_plugin_options_validate( $input ) {
    $newinput['api_key'] = trim( $input['api_key'] );
    if ( ! preg_match( '/^[a-zA-Z0-9\-]{0,64}$/i', $newinput['api_key'] ) ) {
        $newinput['api_key'] = '';
    }
    return $newinput;
}


function chatgpt_add_settings_page() {
    add_options_page( 'ChatGPT Settings', 'ChatGPT Settings', 'manage_options', 'chatgpt-plugin', 'chatgpt_render_plugin_settings' );
}
add_action( 'admin_menu', 'chatgpt_add_settings_page' );

function chatgpt_plugin_section_text() {
    echo '<p>Here you can set all the options for using the API</p>';
}

function chatgpt_plugin_setting_api_key() {
    $options = get_option( 'chatgpt_plugin_options' );
    echo "<input id='chatgpt_plugin_setting_api_key' name='chatgpt_plugin_options[api_key]' type='text' value='" . esc_attr( $options['api_key'] ) . "' />";
}





//----------------------------------------------


function chatgpt_interaction(){
    global $wp_query;
    if(! isset( $wp_query )){
      return; 
    }
    error_reporting(-1);
    ini_set('display_errors', 'On');
    #define( 'WP_DEBUG', true );

    $generated_text = "";
    $image_url = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['prompt'])) {
            $prompt = $_POST['prompt'];
            $temperature = $_POST['temperature'];
            #echo($prompt);
            $options = get_option( 'chatgpt_plugin_options' );
            $api_key = esc_attr( $options['api_key'] );

            $curl = curl_init();
        
            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.openai.com/v1/completions",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"prompt\":\"$prompt\",\"model\":\"text-davinci-003\",\"max_tokens\":1024,\"temperature\":$temperature}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer $api_key"
            ),
            ));
        
            $response = curl_exec($curl);
            $err = curl_error($curl);
        
            curl_close($curl);
        
            if ($err) {
            echo "cURL Error #:" . $err;
            } else {
            $response = json_decode($response, true);
            $generated_text = $response['choices'][0]['text'];
            #echo $generated_text;
            #print_r($response);
            }
        }
    }
    
    $output = '<form action="" method="post">
    <textarea name="prompt"></textarea>
    <input name="temperature" id="temperature" type="number" value="0.5" step="0.1" min="0" max="1"/>
    <input type="submit" value="Submit">
    </form><br>' . $generated_text ;

    return $output;
}

add_shortcode('chatgpt-reader', 'chatgpt_interaction');




function chatgpt_image_interaction(){
    global $wp_query;
    if(! isset( $wp_query )){
      return; 
    }
    error_reporting(-1);
    ini_set('display_errors', 'On');
    #define( 'WP_DEBUG', true );

    $generated_text = "";
    $image_url = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        #if imageprompt
        if (isset($_POST['imageprompt'])) {
            $imageprompt = $_POST['imageprompt'];

            $options = get_option( 'chatgpt_plugin_options' );
            $api_key = esc_attr( $options['api_key'] );

            $url = "https://api.openai.com/v1/images/generations";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $api_key
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{
                "model": "image-alpha-001",
                "prompt": "'. $imageprompt .'",
                "num_images":1,
                "size":"512x512"
            }');

            $output = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($output, true);
            $image_url = $result['data'][0]['url'];
        }
    }
    
    $output = '<form action="" method="post">
    <textarea name="imageprompt"></textarea>
    <input type="submit" value="Submit">
    </form><br><img style="width:100%" src="'. $image_url .'" />';

    return $output;
}

add_shortcode('chatgpt-image', 'chatgpt_image_interaction');

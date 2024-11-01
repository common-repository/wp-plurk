<?php
/*
Plugin Name: WP-Plurk
Plugin URI: http://www.ferdianto.com/wp-plurk
Description: post to plurk whenever post is saved
Author: Herdian Ferdianto
Version: 0.1
Author URI: http://ferdianto.com/
*/

if (!class_exists( 'WPPlurk' )):

class WPPlurk
{
    var $nick_name = '';
    var $uid = '';
    var $cookie = '';

    function login( $uname, $pwd )
    {
        $this->cookie = '';

        $postdata = 'nick_name=' . urlencode($uname) . '&password=' . urlencode($pwd);
        $r = $this->http_request('http://www.plurk.com/Users/login?redirect_page=main', $postdata);
        for ($j = 0; isset($r['cookie'][$j]); $j++)
        {
            if (stristr($r['cookie'][$j], 'plurkcookie'))
            {
                $this->cookie = $r['cookie'][$j];
                break;
            }
        }

        if (isset($r['body']))
        {
            preg_match('/var GLOBAL = \{.*"uid": ([\d]+),.*\}/imU', $r['body'], $matches);
            $this->uid = $matches[1];
            $this->nick_name = $uname;
            return true;
        }

        return false;
    }

    function post($string_lang = 'en', $string_qualifier = 'says', $string_content = '', $allow_comments = true, $array_limited_to = array())
    {
        if (!$this->cookie || !is_string($string_lang) || !is_string($string_qualifier) || !is_string($string_content) || $string_content == '' || ! is_array($array_limited_to) || ! is_bool($allow_comments))
        {
            return false;
        }

        $posted_ = gmdate('c');
        $posted_ = explode('+', $posted_);
        $posted  = urlencode($posted_[0]);

        $qualifier = urlencode(':');
        if ($string_qualifier != '')
        {
            $qualifier = urlencode($string_qualifier);
        }

        if (strlen($string_content) > 140)
        {
            return false;
        }
        $content = urlencode($string_content);

        $no_comments = '1';
        if ($allow_comments == true) {
            $no_comments = '0';
        }

        $lang = urlencode($string_lang);

        $array_query = array(
            'posted'      => $posted,
            'qualifier'   => $qualifier,
            'content'     => $content,
            'lang'        => $lang,
            'uid'         => $this->uid,
            'no_comments' => $no_comments
            );

        if (count($array_limited_to) > 0)
        {
            $limited_to = '[' . implode(',', $array_limited_to) . ']';
            $limited_to = urlencode($limited_to);
            $array_query['limited_to'] = $limited_to;
        }

        $a = array();
        foreach($array_query as $k => $v)
        {
            $a[] = $k . '=' . $v;
        }
        $postdata = implode('&', $a);

        $r = $this->http_request( 'http://www.plurk.com/TimeLine/addPlurk', $postdata, $this->cookie );
        if (!$r['body'])
            return false;
            
        if (preg_match('/anti-flood/', $r['body']))
        {
            return false;
        }

        if (preg_match('/"error":\s(\S+)}/', $r['body'], $error_match))
        {
            if ($error_match[1] != 'null')
            {
                return false;
            }
        }

        return true;
    }

    function http_request($url, $postdata='', $cookie='')
    {
        $response = array();
        if (function_exists('curl_init'))
        {
            // Use CURL if installed...
            $useragent = 'WP-Plurk (curl) ' . phpversion();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($postdata)
            {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            if ($cookie)
            {
                curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            }

            $result = curl_exec($ch);
            if ($result)
            {
                $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
                $response['body'] = substr( $result, $header_size );
                $headers = explode( "\n", substr($result, 0, $header_size));
                foreach($headers as $line)
                {
                    $line = trim($line);
                    if (!$line) continue;

                    if (strstr(strtolower($line), 'set-cookie'))
                    {
                        list($key, $val) = explode( ':', $line, 2 );
                        list($cookie, $param) = explode( ';', $val, 2 );
                        $response['cookie'][] = trim($cookie);
                    }

                }
            }

            curl_close($ch);
        }
        else
        {
            $useragent = 'WP-Plurk (non-curl) ' . phpversion();
            if ($postdata)
            {
                $context =
                    array('http' =>
                          array('method' => 'POST',
                                'header' => "Content-type: application/x-www-form-urlencoded\r\n".
                                            "User-Agent: $user_agent\r\n".
                                            "Content-length: " . strlen($postdata)."\r\n".
                                            "Connection: close",
                                'content' => $postdata));
            }
            else
            {
                $context =
                    array('http' =>
                          array('method' => 'GET',
                                'header' => "User-Agent: $user_agent\r\n".
                                            "Connection: close"));
            }

            if ($cookie)
            {
                $context['http']['header'] .= "\r\nSet-Cookie: $cookie";
            }

            $contextid = stream_context_create($context);
            $sock=fopen($url, 'r', false, $contextid);
            if ($sock)
            {
                $meta = stream_get_meta_data($sock);
                for ($j = 0; isset($meta['wrapper_data'][$j]); $j++)
                {
                    if (strstr(strtolower($meta['wrapper_data'][$j]), 'set-cookie'))
                    {
                        $line = $meta['wrapper_data'][$j];
                        list($key, $val) = explode( ':', $line, 2 );
                        list($cookie, $param) = explode( ';', $val, 2 );
                        $response['cookie'][] = trim($cookie);
                    }
                }
                $response['body']='';
                while ($line = fgets($sock, 4096)) $response['body'].= $line;
                fclose($sock);
            }
        }

        return $response;
    }
}
endif;

add_action( 'admin_menu', 'wpplurk_add_menu' );

function wpplurk_add_menu()
{
    add_options_page('Setup WP-Plurks', 'wp-plurks', 8, 'wp_plurk.php', 'wpplurk_options');
}

function wpplurk_options()
{
    ?>
    <div class="wrap">
    <h2>WP-Plurk Options</h2>

    <form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>

    <table class="form-table">
    <tr valign="top">
        <th scope="row">Plurk Username</th>
        <td><input type="text" name="wpplurk_user" value="<?php echo get_option('wpplurk_user'); ?>" size="20" /></td>
    </tr>
    <tr valign="top">
        <th scope="row">Plurk Password</th>
        <td><input type="password" name="wpplurk_pass" value="<?php echo get_option('wpplurk_pass'); ?>"  size="20" /></td>
    </tr>
    <tr valign="top">
        <th scope="row">Plurk Template</th>
        <td><input type="text" name="wpplurk_template" value="<?php $tpl = get_option('wpplurk_template'); echo $tpl ? $tpl : '[shares] {{url}} - {{title}}' ?>"  size="50" /></td>
    </tr>
    </table>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="wpplurk_user,wpplurk_pass,wpplurk_template" />

    <p class="submit">
    <input type="submit" class="button-primary" value="Save" />
    </p>

    </form>
    </div>
    <?php
}

add_action('publish_post', 'wpplurk_doplurk');

function wpplurk_doplurk($post_id)
{
    if (wp_is_post_revision($post_id))
        return $post_id;

    //is already plurked?
    $is_plurked = get_post_meta( $post_id, '_plurked', true );
    if ($is_plurked)
        return $post_id;

    $plurk_user = get_option('wpplurk_user');
    $plurk_pass = get_option('wpplurk_pass');
    $plurk_template = get_option('wpplurk_template');

    //username or password not set, no need to plurk
    if (!$plurk_user || !$plurk_pass)
        return $post_id;

    if (!$plurk_template)
        $plurk_template = '[shares] ({{url}}) {{title}}';

    $post = get_post( $post_id );
    $title = $post->post_title;
    $permalink = post_permalink($post_id);

    preg_match( '!(^\[(.+?)\])?(.*)!', $plurk_template, $matches );
    $qualifier = $matches[2];
    $plurk_text = $matches[3];
    if (!$qualifier) $qualifier  = 'shares';

    $plurk_text = str_replace(array('{{url}}', '{{title}}'),
                              array($permalink, $title),
                              $plurk_text);

    $plurk = new WPPlurk;
    $login = $plurk->login( $plurk_user, $plurk_pass );
    if (!$login)
        return $post_id;

    $result = $plurk->post('en', $qualifier, $plurk_text, true);
    if (!$result)
        return $post_id;

    //mark as plurked
    add_post_meta( $post_id, '_plurked', '1' );
    
    return $post_id;
}

?>
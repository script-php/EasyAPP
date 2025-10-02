<?php

/**
* @package      Library User
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c), script-php.ro
* @link         https://script-php.ro
* 
* @recommendation Always hash passwords before storing and before passing to sign_in()
* @recommendation Use strong hashing algorithms like bcrypt or Argon2
* @recommendation Never store or compare plain text passwords
*/

class LibraryUser extends Library {

    public $user_info = [
        'user_id' => 0,
        'user_group_id' => 0,
        'username' => 'Guest'
    ];
    public $permission = [];
    public $logged = false;

    function __construct($registry) {
        parent::__construct($registry);

        $fingerprint = $this->request->fingerprint();
    
        if(!empty($this->request->cookie[CONFIG_SESSION_NAME]) && $fingerprint !== NULL) {
    
            $fingerprint = base64_encode($fingerprint);
    
            $session_query = $this->db->query("SELECT session, user, lastactive, fingerprint FROM user_session us LEFT JOIN user u ON u.user_id=us.user WHERE us.session=:session AND us.fingerprint=:fingerprint AND us.status='1'", [
                ":session"      => $this->request->cookie[CONFIG_SESSION_NAME],
                ":fingerprint"  => $fingerprint
            ]);

            if($session_query->num_rows) {
                $session_data = $session_query->row;

                $user_query = $this->db->query("SELECT * FROM user u LEFT JOIN uploads upl ON upl.upload_id=u.image WHERE u.user_id = :user_id AND u.status='1'", [':user_id'=>$session_data['user']]);
                if($user_query->num_rows) {

                    // permissions
                    $this->user_info = $user_query->row;

                    $permissions = $this->db->query("SELECT permission FROM user_group WHERE user_group_id = :user_group_id", [':user_group_id'=>$this->user_info['user_group_id']]);
                    
                    if($permissions->num_rows) {
                        $user_group_query = $permissions->row;
                        $permissions = json_decode($user_group_query['permission'], true);
                        if (is_array($permissions)) {
                            foreach ($permissions as $key => $value) {
                                $this->permission[$key] = $value;
                            }
                        }
                    }
                    // permissions

                    // time spent online
                    $total_time = !empty($session_data['lastactive']) ? ($_SERVER['REQUEST_TIME']-strtotime($session_data['lastactive'])) : 0;
                    $this->db->query("UPDATE user_session SET lastactive=NOW() WHERE session=:session", [':session'=>$session_data['session']]);
                    if($total_time <= 300) {
                        //$this->db->query("UPDATE user SET timeonline=timeonline+:total_time, online=NOW() WHERE user_id=:utilizator", ['total_time'=>$total_time,':utilizator'=>$session_data['user']]);
                    }
                    // time spent online

                }

                $this->logged = true;
            }

        }

    }

    function userinfo(string $key = '') {
        if(!empty($key)) {
            return $this->user_info[$key];
        }
        return $this->user_info;
    }

    function sign_in($user, $email, $password, $url) {

        $user_query = $this->db->query("SELECT * FROM user WHERE username = :user AND password = :password OR email = :email AND password = :password2 AND status = '1'", [':user'=>$user,':email'=>$email,':password'=>$password,':password2'=>$password]);

        if ($user_query->num_rows && !$this->logged) {

            $this->request->data['user_info'] = $user_query->row;

			$this->user_info = $user_query->row;
			$permissions = $this->db->query("SELECT permission FROM user_group WHERE user_group_id = :user_group_id", [':user_group_id'=>$this->user_info['user_group_id']]);

            if($permissions->num_rows) {
                $user_group_query = $permissions->row;
                $permissions = json_decode($user_group_query['permission'], true);
                if (is_array($permissions)) {
                    foreach ($permissions as $key => $value) {
                        $this->permission[$key] = $value;
                    }
                }
            }

            $token = $this->util->random(4, 8, false, true, true) . '-' . $this->util->random(4, 8, false, true, true) . '-' . $this->util->random(4, 8, false, true, true) . '-' . $this->util->random(4, 8, false, true, true) . '-' . $this->util->random(4, 8, false, true, true);

            $timestamp = time();

            $session = $this->db->query("INSERT INTO user_session(session,user,ip,country,useragent,fingerprint,lastactive,date_added) VALUES (:session,:user,:ip,:country,:useragent,:fingerprint,NOW(),NOW())", [
                ":session"		=> $token,
                ":user"			=> $this->user_info['user_id'],
                ":ip"			=> $this->request->ip(),
                ":country"		=> 'UNKNOWN', // TODO: ADD A CLASS TO GET THE COUNTRY FROM IP
                ":useragent"	=> $this->util->chars2Html($_SERVER['HTTP_USER_AGENT']),
                ":fingerprint"	=> base64_encode($this->request->fingerprint())
            ]);

            if($session) {
                $this->request->setcookie(CONFIG_SESSION_NAME, $token, time()+CONFIG_SESSION_TIME, '/', CONFIG_DOMAIN);
                if(!empty($this->request->cookie[CONFIG_SESSION_NAME]) && ($this->request->cookie[CONFIG_SESSION_NAME] == $token)) {
                    $this->request->redirect($url);
                }
            }

			return true;
		} else {
			return false;
		}
    }

    function sign_out() {
        $this->request->setcookie(CONFIG_SESSION_NAME, NULL, time()-CONFIG_SESSION_TIME, '/', CONFIG_DOMAIN);
        return true;
    }

    function signed() {
        return $this->logged;
    }

    function permission($key, $value) {
        if (isset($this->permission[$key])) {
			return in_array($value, $this->permission[$key]);
		} else {
			return false;
		}
    }
    
}
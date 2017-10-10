<?php
class User{
	public $id;
	public $username;
	public $name;
	public $type;
    public $permission;
	public $status;
    public $ip;
	public $register_time;
	public $visit_time;

	private $password;
	private $salt;
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function getUser($user_id){
    	$this->db->query('SELECT id,username,name,password,salt,type,permission,status,ip,register_time,visit_time FROM api_user WHERE id = :user_id');
		$this->db->bind(':user_id',$user_id);
		$this->db->execute();
		$dataset = $this->db->single();

		$this->id             = $dataset['id'];
		$this->username       = $dataset['username'];
		$this->name           = $dataset['name'];
		$this->password       = $dataset['password'];
		$this->salt           = $dataset['salt'];
		$this->permission     = $dataset['permission'];
        $this->ip             = $dataset['ip'];
		$this->type           = $dataset['type'];
		$this->status         = $dataset['status'];
		$this->register_time  = $dataset['register_time'];
		$this->visit_time     = $dataset['visit_time'];
    }

    public function sec_session_start() {
        $session_name   = 'sec_session_id';   // Set a custom session name
        $secure         = false;
        // session.cookie_secure specifies whether cookies should only be sent over secure connections. (https)

        // This stops JavaScript being able to access the session id.
        $httponly = true;

        // Forces sessions to only use cookies.
        if(ini_set('session.use_only_cookies', 1) === FALSE) {
            header("Location: ../error.php?err=Could_not_initiate_a_safe_session");
            exit();
        }

        // Gets current cookies params.
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(600,$cookieParams["path"],$cookieParams["domain"],$secure,$httponly);
        // session_set_cookie_params('600'); // 10 minutes.

        // Sets the session name to the one set above.
        session_name($session_name);
        session_start();             // Start the PHP session
        // session_regenerate_id(true); // regenerated the session, delete the old one.
    }

    public function loginChecking(){
        // READ COOKIES
        if(!empty($_COOKIE['user_id']) && empty($_SESSION['user_id']))
        	$_SESSION['user_id'] = $_COOKIE['user_id'];
        if(!empty($_COOKIE['login_string']) && empty($_SESSION['login_string']))
        	$_SESSION['login_string'] = $_COOKIE['login_string'];

        // Check if all session variables are set
        if(isset($_SESSION['user_id'],$_SESSION['login_string'])){

            $user_id        = $_SESSION['user_id'];
            $login_string   = $_SESSION['login_string'];

            // Get the user-agent string of the user.
            $user_browser   = $_SERVER['HTTP_USER_AGENT'];

            $this->getUser($this->Decrypt($user_id));

            if(!empty($this->id)){
                $login_check = hash('sha512',$this->password.$user_browser);

                if($login_check == $login_string){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function login($username,$password){
        $username       = strip_tags(trim($username));
        $password       = trim($password);
        $cookie_time    = time() + 3600 * 24 * 12; // Cookie Time (1 year)

        // GET USER DATA BY EMAIL
        $this->db->query('SELECT id,password,salt FROM api_user WHERE username = :username');
		$this->db->bind(':username',$username);
		$this->db->execute();
		$user_data = $this->db->single();

		if($this->checkBrute($user_data['id'])){
			if((hash('sha512',$password.$user_data['salt']) == $user_data['password'])){
				// PASSWORD IS CORRECT!
				$user_browser = $_SERVER['HTTP_USER_AGENT'];

				// XSS protection as we might print this value
				$user_id = preg_replace("/[^0-9]+/",'',$user_data['id']);
				// Encrypt UserID before send to cookie.
				$user_id = $this->Encrypt($user_id);

				// SET SESSION AND COOKIE
				$_SESSION['user_id'] = $user_id;
				setcookie('user_id',$user_id,$cookie_time);
				$_SESSION['login_string'] = hash('sha512',$user_data['password'].$user_browser);
				setcookie('login_string',hash('sha512',$user_data['password'].$user_browser),$cookie_time);

				// Save log to attempt : [successful]
				// $this->db->recordAttempt($user_data['id'],'successful');

				return 1; // LOGIN SUCCESS
			}else{
				// Save log to attempt : [fail]
				if(!empty($user_data['id'])){
					$this->recordAttempt($user_data['id']); // Login failure!
				}

				return 0; // LOGIN FAIL!
			}
		}else{
			return -1; // ACCOUNT LOCKED!
		}
        // Note: crypt — One-way string hashing (http://php.net/manual/en/function.crypt.php)
    }

    private function checkBrute($user_id){
        // First step clear attempt log.
        // $this->db->clearAttempt();
        // return ($this->db->countAttempt($user_id) >= 5 ? true : false);

        return true;
    }

    private function userAlready($email){
		$this->db->query('SELECT id FROM user WHERE email = :email');
		$this->db->bind(':email',$email);
		$this->db->execute();
		$dataset = $this->db->single();
		
		if(empty($dataset['id'])) return true;
		else return false;
	}

	private function Encrypt($data){
        $password = $this->cookie_salt;
        $salt = substr(md5(mt_rand(), true), 8);
        $key = md5($password . $salt, true);
        $iv  = md5($key . $password . $salt, true);
        $ct = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
        return base64_encode('Salted__' . $salt . $ct);
    }

    private function Decrypt($data){
        $password = $this->cookie_salt;
        $data = base64_decode($data);
        $salt = substr($data, 8, 8);
        $ct   = substr($data, 16);
        $key = md5($password . $salt, true);
        $iv  = md5($key . $password . $salt, true);
        $pt = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ct, MCRYPT_MODE_CBC, $iv);
        return $pt;
    }


    // LOGIN ATTEMPTS
	private function recordAttempt($user_id){
		$this->db->query('INSERT INTO login_attempts(user_id,time,ip) VALUE(:user_id,:time,:ip)');
		$this->db->bind(':user_id' ,$user_id);
		$this->db->bind(':time' 	,time());
		$this->db->bind(':ip' 		,$this->db->GetIpAddress());

		$this->db->execute();
		return $this->db->lastInsertId();
	}
	// public function countAttempt($member_id){
	// 	$this->db->query('SELECT COUNT(member_id) total FROM login_attempts WHERE (member_id = :member_id) AND (status = "fail")');
	// 	$this->db->bind(':member_id', $member_id);
	// 	$this->db->execute();
	// 	$data = $this->db->single();
	// 	return $data['total'];
	// }

	private function clearAttempt(){
		$this->db->query('DELETE FROM login_attempts WHERE time < :limittime');
		$this->db->bind(':limittime', time() - 60);
		$this->db->execute();
	}

    public function editProfile($user_id,$username,$displayname){
        $this->db->query('UPDATE api_user SET username = :username, name = :displayname, edit_time = :edit_time WHERE id = :user_id');
        $this->db->bind(':user_id' ,$user_id);
        $this->db->bind(':username' ,$username);
        $this->db->bind(':displayname' ,$displayname);
        $this->db->bind(':edit_time' ,date('Y-m-d H:i:s'));

        $this->db->execute();
    }
    public function changePassword($user_id,$oldpassword,$newpassword){
        // Random password if password is empty value
        $salt = hash('sha512',uniqid(mt_rand(1,mt_getrandmax()),true));
        // Create salted password
        $password   = hash('sha512',$newpassword.$salt);

        $this->db->query('UPDATE api_user SET password = :password, salt = :salt, edit_time = :edit_time WHERE id = :user_id');
        $this->db->bind(':user_id' ,$user_id);
        $this->db->bind(':password' ,$password);
        $this->db->bind(':salt' ,$salt);
        $this->db->bind(':edit_time' ,date('Y-m-d H:i:s'));
        $this->db->execute();
    }
}
?>
<?php
/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 01/01/2009
 * @lastmod		21/02/2009 12:54
 * @desc	   	Authentification des utilisateurs
 *
 **/
class UserAuth 
	{
	 // Déclaration des variables
	 var $member_area 		= "member_area.php"; 
	 var $admin_area 		= "admin.php"; 
	 var $login_page 		= "identification.php"; 
	 var $logout_page 		= "../index.php"; 
	 var $error_form 		= "Merci de compléter le formulaire"; 
	 var $error_user 		= "Identifiant et/ou mot de passe incorrect !"; 
	 var $idle_time 		= 18000; //Secondes
	
	 var $identifiant;
	 var $mdp;
	 var $email;
		
	/**
	 * Constructor
	 **/
	function UserAuth()
		{
		/* Démarre la session */
		 @session_start();
		}
	
	/**
	 * @return 	bool
	 * @desc 	Verify if user has got a session and if the user's IP corresonds to the IP in the session.
	 **/
	function verifySession() 
		{
		 if (!isset($_SESSION["identifiant"])) 
			 return false;
		 else {
			if ( time() - $_SESSION["idle"] > $this->idle_time ) return false;
			else {
				$_SESSION["idle"] = time();
				return true;
				}
			}
			 
		}
	 
	/**
	 * @return void
	 * @param string $page
	 * @desc Redirect the browser to the value in $page.
	 **/
	function redirect($page) 
		{
		 header("Location: ".$page);
		 exit();
		}
		
	/**
	 * @return 	bool
	 * @desc 	Verifie l'identifinat et le mot de passe avec la BdD.
	 **/
	function verifyDB() 
		{
		 global $db;
		
		 $sql = "SELECT * FROM utilisateurs 
		 		 WHERE identifiant = '".$this->identifiant."' AND mdp = '".$this->mdp."' 
				 AND expiration > CURRENT_DATE";
		 $row = $db->getRow($sql);
		 if ($row) 
			{
			 $this->id_utilisateur    	= $row->id_utilisateur;
			 $this->nom    	   		  	= $row->nom;
			 $this->prenom     			= $row->prenom;
			 $this->email      			= $row->email;
			 $this->profil     			= $row->profil ;
			 return true;
			}
		 else 
			 return false;
		}
		
	/**
	 * @return void
	 * @desc Write identifiant, email and IP into the session.
	 **/
	function writeSession() 
		{
		  global $db;
		 // Sauve la date de dernière connexion
		 $sql = "UPDATE utilisateurs SET last_cnx = CURRENT_TIMESTAMP WHERE id_utilisateur ='$this->id_utilisateur'"; 
		 $db->query($sql);
		 
		 $_SESSION["id_utilisateur"]   	= $this->id_utilisateur;
		 $_SESSION["identifiant"]  		= $this->identifiant;
		 $_SESSION["nom"]  				= $this->nom;
		 $_SESSION["prenom"]  			= $this->prenom;
		 $_SESSION["email"] 			= $this->email;
		 $_SESSION["profil"]			= $this->profil;
         $_SESSION["idle"]      		= time();
		}
	
	   
	/**
	 * @return 	bool
	 * @desc 	Verify if login form fields were filled out.
	 **/
	function verifyForm() 
		{
		 if (isset($_POST["identifiant"]) && isset($_POST["mdp"]) && $_POST["identifiant"] != "" && $_POST["mdp"] != "") 
			{
			 $this->identifiant = $_POST["identifiant"];
			 $this->mdp = md5($_POST["mdp"]);
			 return true;
			}
		 else 
			return false;
		}
		
	/**
	 * @return string
	 * @desc 
	 **/
	function login() 
		{
		 // verify if user is already logged in
		 if ($this->verifySession()) {
				if ($_SESSION["profil"] > 1) $this->redirect($this->admin_area);
				else $this->redirect($this->member_area);
			}
		 // verify if login form is complete
		 if (!$this->verifyForm()) 
			{
			 if (isset($_POST["identifiant"]) && isset($_POST["mdp"])) 
				return $this->error_form;
			}
			
		 // verify if form's data coresponds to database's data
		 else 
			{
			if (!$this->verifyDB()) 
				return $this->error_user;
			else 
				{
				$this->writeSession();
				if ($_SESSION["profil"] > 1)$this->redirect($this->admin_area);
				else $this->redirect($this->member_area);
				}
			}
		}
		
	/**
	 * @return void
	 * @desc The user will be logged out.
	 **/
	function logout() 
		{
		 $_SESSION = array();
		 session_unset();
		 session_destroy();
		 header("Location: ".$this->logout_page);
		}
		
	/**
	 * @return void
	 * @desc If the user isn't logged on or there aren't
	 *       any cookies or the session terminated, the
	 *       user will be redirected to the login page.
	 **/
	function loggedin() 
		{
		 // verify if user is already logged in
		 if (!$this->verifySession()) 
			 $this->redirect($this->login_page);
		}
	
	/**
	 * @return 	identifiant
	 * @desc 	Retourne l'identifiant
	 **/
	function getidentifiant() 
		{
		 if (isset($_SESSION["identifiant"])) 
			  return $_SESSION["identifiant"];
		}
	
	/**
	 * @return 	identifiant
	 * @desc 	Retourne l'identifiant
	 **/
	function getUserEmail() 
		{
		 if (isset($_SESSION["email"])) 
			  return $_SESSION["email"];
		}
	

	/**
	 * @return 	UserIdentity
	 * @desc 	Retourne le prenom et le nom de l'utilisateur
	 **/
	function getUserIdentity() 
		{
		 if (isset($_SESSION["prenom"]) && isset($_SESSION["nom"])) 
			  return $_SESSION["prenom"]." ".$_SESSION["nom"];
		}
	
	/**
	 * @return 	UserID (int)
	 * @desc 	Retourne le UserID
	 **/
	 function getUserID() 
		{
		 if (isset($_SESSION["user_id"])) 
			  return $_SESSION["user_id"];
		}
	/**
	 * @return 	UserProfil (int)
	 * @desc 	Retourne le UserProfil
	 **/
	 function getUserProfil() 
		{
		 if (isset($_SESSION["profil"])) 
			  return $_SESSION["profil"];
		}
	}
?>
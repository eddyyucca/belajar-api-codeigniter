<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */

	public function __construct(){
		    parent::__construct();
		    $this->load->model('mdl_api');
		    // $config['rest_valid_logins']
	 }
	public function index()
	{
		echo "tes";
	}

	function testSendNotif($id_user) {	    
		define('API_ACCESS_KEY','AAAAWbOE2Ew:APA91bFOJOVNTNrrrbzqhIO2Q_P5QPL0_SHgJ9liuEA4tY36E1ZSVzt1Rj97rvQLSImUvpyrUoT88w2PkS0iDSAMsYeufspaCD1rzZQoQPvui5IgOJNL-kNT55sETKhPXAvVeG-dqAr2');
		$fcmUrl = 'https://fcm.googleapis.com/fcm/send';
		 //$token='235zgagasd634sdgds46436';

		     $notification = [
		            'title' =>'Judul',
		            'body' => 'Body pesan.'
		        ];
		        $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

		        $fcmNotification = [
		            //'registration_ids' => $tokenList, //multple token array
		            'to'        => "/topis/$id_user", //single token
		            'notification' => $notification//,
		            //'data' => $extraNotificationData
		        ];

		        $headers = [
		            'Authorization: key=' . API_ACCESS_KEY,
		            'Content-Type: application/json'
		        ];


		        $ch = curl_init();
		        curl_setopt($ch, CURLOPT_URL,$fcmUrl);
		        curl_setopt($ch, CURLOPT_POST, true);
		        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
		        $result = curl_exec($ch);
		        curl_close($ch);


		        echo $result;
	}
	
	function test_sendemail($subjek,$pesan) {
		   	$tujuan="hudauzumaki@gmail.com";
		        $config = [
		               'mailtype'  => 'html',
		               'charset'   => 'utf-8',
		               'protocol'  => 'smtp',
		               'smtp_host' => 'ssl://smtp.googlemail.com',
		               'smtp_user' => 'dlc.dosenku@gmail.com',    // Ganti dengan email gmail kamu
		               'smtp_pass' => 'Martapura2',      // Password gmail kamu
		               'smtp_port' => 465,
		               'crlf'      => "rn",
		               'newline'   => "\r\n"
		           ];

		            // Load library email dan konfigurasinya
		            $this->load->library('email', $config);

		            // Email dan nama pengirim
		            $this->email->from('dlc.dosenku@gmail.com', 'Dosenku');

		            // Email penerima
		            $this->email->to($tujuan); // Ganti dengan email tujuan kamu

		            // Lampiran email, isi dengan url/path file
		            //$this->email->attach('https://masrud.com/content/images/20181215150137-codeigniter-smtp-gmail.png');

		            // Subject email
		            $this->email->subject($subjek);

		            // Isi email
		            $this->email->message($pesan."<br/><br/>Contact center : <br/>1. Ririn / 0853 4555 3400<br/>2. Adie / 0812 5551 8486");

		            // Tampilkan pesan sukses atau error
		            if ($this->email->send()) {
		                //echo "sukses";
		                return TRUE;
		            } else {                
		                echo $this->email->print_debugger();
		                return FALSE;
		            }
		}		
	
}

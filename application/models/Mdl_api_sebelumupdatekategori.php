<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 1. Ganti Mdl_perfectmodels dengan nama model.
// 2. Ganti 'tablename' dengan nama tabel.


class Mdl_api extends CI_Model {

function __construct() {
    parent::__construct();
}


function cekpasswordlama($id_user,$p_lama,$token) {
	$this->db->where('id_user',$id_user);
	$this->db->where('password_user',md5($p_lama));
	$this->db->where('token',$token);
	$query=$this->db->get('t_user');
	return $query->row();
}

function gantipassword($id_user,$p_baru) {
	$this->db->where('id_user',$id_user);
	$data['password_user']=md5($p_baru);

	$this->db->update('t_user',$data);
}

function getinfo() {
	$this->db->where('status_info','aktif');
	$this->db->order_by('date_modify','DESC');
	$this->db->order_by('id_info','ASC');
	$query=$this->db->get('t_info');
	return $query->result();
}

function gethargamemberbaru() {
	$this->db->order_by('id_setting_harga','DESC');
	$this->db->limit(1);
	$query=$this->db->get('m_setting_harga');

	if ($query->num_rows()==0) {
		return "0";		
	} else {
		$row=$query->row();
		return $row->member_baru;
	}
}

function getlistprovinsi() {
	$this->db->order_by('nama_provinsi','ASC');
	$query=$this->db->get('m_provinsi');
	return $query->result();
}

function cekloginuser($email_user, $password_user) {
	$this->db->where('a.email_user',$email_user);
	$this->db->where('a.password_user',md5($password_user));
	$this->db->where('a.level_user','member');
	$now=date('Y-m-d');
	//$this->db->where('b.expired_member >=',$now);
	$this->db->join('t_member b','a.id_user=b.id_user',"INNER");
	$this->db->join('m_perguruan_tinggi c','b.id_perguruan_tinggi=c.id_perguruan_tinggi',"INNER");
	$this->db->join('m_provinsi d','c.id_provinsi=d.id_provinsi');

	$query=$this->db->get('t_user a');
	return $query->row();
}

function updatetoken($id_user,$token) {
	$this->db->where('id_user',$id_user);
	$data['token']=$token;
	$data['last_login']=date('Y-m-d H:i:s');

	$this->db->update('t_user',$data);
}

function checkexistedemail($email_user) {
	$this->db->where('email_user',$email_user);
	$query=$this->db->get('t_user');
	return $query->result();
}

function getidperguruantinggi($nama_perguruan_tinggi,$id_provinsi) {
	$this->db->where('id_provinsi',$id_provinsi);
	$this->db->where('nama_perguruan_tinggi',$nama_perguruan_tinggi);
	$query=$this->db->get('m_perguruan_tinggi');
	$row=$query->row();

	if ($row) {
		// artinya sudah ada
		return $row->id_perguruan_tinggi;
	} else {
		// artinya belum ada, insert dan return id nya
		$data['nama_perguruan_tinggi']=strtoupper($nama_perguruan_tinggi);
		$data['id_provinsi']=$id_provinsi;
		$this->db->insert('m_perguruan_tinggi',$data);

		 $insert_id = $this->db->insert_id();
   		return  $insert_id;
	}
}

function gethargamember($tipe='baru') {
	$this->db->order_by('id_setting_harga','DESC');
	$this->db->limit(1);
	$query=$this->db->get('m_setting_harga');
	$row=$query->row();
	if ($row) {
		if ($tipe=='baru') {
			return $row->member_baru;	
		} else {
			return $row->member_perpanjang;
		}		
	} else {
		return '0';
	}
	
}

function cektokenuser($id_user,$token) {
	$this->db->where('a.id_user',$id_user);
	$this->db->where('a.token',$token);
	//$this->db->where('b.expired_member  >= ', date("Y-m-d"));
	$this->db->join('t_member b','a.id_user=b.id_user');
	$query=$this->db->get('t_user a');
	return $query->row();
}

function getmembershipdetail($id_user) {
	$this->db->where('id_user',$id_user);
	$query=$this->db->get('t_member');
	return $query->row();
}

function updatestatusmember($id_user) {
	// untuk update member apakah sudah expired atau masih aktif atau masih baru bersesuaian dengan field expired_member
	$this->db->where('id_user',$id_user);
	$query=$this->db->get('t_member');
	$row=$query->row();

	if ($row->expired_member>=date('Y-m-d')) {
		$data['status_member']='aktif';
		$this->db->where('id_user',$id_user);
		$this->db->update('t_member',$data);
	} elseif ($row->status_member=='aktif') {
		// artinya statusnya aktif tapi sudah lewat tanggalnya. UPDATE JADI ESXPIRED
		$data['status_member']='expired';
		$this->db->where('id_user',$id_user);
		$this->db->update('t_member',$data);
	}
	return $row->status_member;
}

function getdetailuser($id_user) {
	$this->db->where('t_member.id_user',$id_user);
	$this->db->join('t_user','t_user.id_user=t_member.id_user');
	$query=$this->db->get('t_member');
	return $query->row();
}

function ubahstatusperpanjanguser($id_user,$nominal_member) {
	$this->db->where('id_user',$id_user);
	$data['status_member']="perpanjang";
	$data['nominal_member']=$nominal_member;
	$this->db->update('t_member',$data);
}

function generaterandomstring($len=30){
    $result = "";
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789$11";
    $charArray = str_split($chars);
    for($i = 0; $i < $len; $i++){
	    $randItem = array_rand($charArray);
	    $result .= "".$charArray[$randItem];
    }
    return $result;
}

function getdetailsoal($id_paket_soal) {
	$this->db->where('id_paket_soal',$id_paket_soal);
	$query=$this->db->get('t_paket_soal_dtl');
	return $query->result();
}

function getdetailpaketsoal($id_paket_soal) {
	$this->db->where('id_paket_soal',$id_paket_soal);
	$query=$this->db->get('t_paket_soal_hdr');
	return $query->row();
}

function konversitanggal($format, $tanggal="now", $bahasa="id"){
            $en=array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday",
                      "January","February","March","April","May","June","July","August","September","October","November","December");

            $id=array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu",
                      "Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","Nopember","Desember");

            // mengganti kata yang berada pada array en dengan array id, fr (default id)
            return str_replace($en,$$bahasa,date($format,strtotime($tanggal)));
 }



}
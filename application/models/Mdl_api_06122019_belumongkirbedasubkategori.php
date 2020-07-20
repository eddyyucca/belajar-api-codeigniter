<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 1. Ganti Mdl_perfectmodels dengan nama model.
// 2. Ganti 'tablename' dengan nama tabel.


class Mdl_api extends CI_Model {

function __construct() {
    parent::__construct();
}

public $perpageproduk=10;

function getSuccessResponseVariable($message) {
	$data['status']='200';
	$data['success']=true;
	$data['message']=$message;
	return $data;
}

function cektokenuser($id_user,$token) {
	$this->db->where('a.ID_USER',$id_user);
	$this->db->where('a.TOKEN_USER',$token);
	$query=$this->db->get('user a');
	return $query->row();
}

function getUserDetail($id_user) {
	$this->db->where('ID_USER',$id_user);
	$query=$this->db->get('user');
	return $query->row();
}

function generateRandomString($length = 10, $withalphabet=false) {
	if ($withalphabet==false) {
		$characters = '0123456789';
	} else {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}	
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
	    $randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function konversitanggal($format,$nilai="now"){
        if (($nilai=='0000-00-00')||($nilai=="")||(is_null($nilai))) {
            return "";
        } else {
         $en=array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","January","Februari",
            "March","April","May","June","July","August","September","October","Novemver","December");
            $id=array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu",
            "Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
            return str_replace($en,$id,date($format,strtotime($nilai)));
        }    
    }

function cekEksisNomorWhatsapp($nowhatsapp) {
	$this->db->where('NO_WHATSAPP_USER',$nowhatsapp);
	$query=$this->db->get('user');
	$result=$query->row();
	if ($result) {
		return true;
	} else {
		return false;
	}
}

function registerUser($nama,$whatsapp,$imei="") {
	$password=$this->generateRandomString(5);

	// cek dulu apakah sudah ada nomor whatsappnya, jika sudah maka update dan kirim password lagi. Jika belum maka insert dan kirim password
	if ($this->cekEksisNomorWhatsapp($whatsapp)) {
		$this->db->where('NO_WHATSAPP_USER',$whatsapp);
		$update['PASSWORD_USER']=md5($password);
		$this->db->update('user',$update);
	} else {
		$data['NAMA_USER']=$nama;
		$data['NO_WHATSAPP_USER']=$whatsapp;
		$data['PASSWORD_USER']=md5($password);
		$data['DATE_ADD']=date('Y-m-d H:i:s');
		$data['TOKEN_USER']=$this->generateRandomString(7,true);
		$data['IMEI_USER']=$imei;
		$this->db->insert('user',$data);
	}
	// send password
	$pesan="Selamat bergabung di BakulEmak. \nPassword anda adalah : *$password* \nSilahkan login di aplikasi dengan memasukkan nomor whatsapp dan password anda. Terima kasih.";
	$response=$this->sendWhatsapp($whatsapp,$pesan);
	return $response;

}

function cekeksistingnomorhp($no_whatsapp) {
	$this->db->where('NO_WHATSAPP_USER',$no_whatsapp);
	$query=$this->db->get('user');
	if ($query->num_rows()>0) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function sendWhatsapp($nowhatsapp,$pesan) {
	// konversi nomor whtasapp
	if (substr($nowhatsapp,0,1)=='0') {
		$nowhatsapp=ltrim($nowhatsapp,'0');
		$nowhatsapp="+62".$nowhatsapp;
	}


	$token = '941787fa6b9ac14062cc6d27eb0ae2a6be03456d98e5ef50';
    $data = array(
        'phone_no' => $nowhatsapp, 
        'key' => $token, 
        'message' => $pesan
    );
    $data_string = json_encode($data);
    $ch = curl_init('http://send.woonotif.com/api/send_message');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
        )
    );
    $result = curl_exec($ch);
    return $result;
}

function getPromo() {
	$this->db->order_by('id_promo','ASC');
	$query=$this->db->get('promo');
	return $query->result();
}

function getKategori() {
	$this->db->order_by('ID_KATEGORI','ASC');
	$this->db->where('AKTIF','1');
	$query=$this->db->get('kategori');
	return $query->result();
}

function getjumlahprodukkategori($id_kategori) {
	$this->db->where('STATUS_PRODUK','1');
	$this->db->where('ID_KATEGORI',$id_kategori);
	$query=$this->db->get('produk');
	return $query->num_rows();
}

function getlistproduk($id_kategori,$keyword="",$paging=1) {
	$limit=$this->perpageproduk;
	if ($paging=="") { $page=0; } else { $page=$paging; }
    if ($page>0) {
        $offset=($limit*$page)-$limit;
    } else {
        $offset="0";
    }   
    $this->db->limit($limit, $offset);

	$this->db->where('ID_KATEGORI',$id_kategori);
	$this->db->where('STATUS_PRODUK','1');
	$this->db->where('SHOW_PRODUK','1');
	if ($keyword!="") {
		$this->db->where(" (KODE_PRODUK LIKE '%$keyword%' OR NAMA_PRODUK LIKE '%$keyword%') ");
	}
	$query=$this->db->get('produk');
	return $query->result();
}

function cekstatusnewprodukuser($id_produk,$id_user) {
	$this->db->where('ID_PRODUK',$id_produk);
	$this->db->where('ID_USER',$id_user);
	$query=$this->db->get('new_product_user');
	$row=$query->row();
	if ($row) {
		// artinya tidak baru
		return false;
	} else {
		// artinya baru, dan insert ke new_product_user
		$data['ID_PRODUK']=$id_produk;
		$data['ID_USER']=$id_user;
		$this->db->insert('new_product_user',$data);
		return true;
	}
}

function gettokoterdekat($lat_user,$long_user) {
	$query="SELECT ID_TOKO, LAT_TOKO, LONG_TOKO, (6371 * 2 * ASIN(SQRT( POWER(SIN(( $lat_user - LAT_TOKO) *  pi()/180 / 2), 2) +COS( $lat_user * pi()/180) * COS(LAT_TOKO * pi()/180) * POWER(SIN(( $long_user - LONG_TOKO) * pi()/180 / 2), 2) ))) as distance  FROM toko  ORDER BY distance LIMIT 1";
	$query=$this->db->query($query);
	return $query->row();
}

function hitungbiayaantar($jarak) {
	$this->db->order_by('ID_SETTING','DESC');
	$query=$this->db->get('setting_harga');
	$row=$query->row();
	if ($jarak<5) {
		return $row->JARAK_0_5;
	} else {
		return $row->JARAK_5_10;
	}
}

function getLastPesananUser($id_user) {
	$this->db->where('ID_USER',$id_user);
	$this->db->order_by('TANGGAL_PESANAN','DESC');
	$query=$this->db->get('pesanan');
	return $query->row();
}

function getlistpesananuser($id_user) {
	$this->db->where('ID_USER',$id_user);
	$this->db->order_by('ID_PESANAN',"DESC");
	$query=$this->db->get('pesanan');
	return $query->result();
}

function getjumlahitempesanan($id_pesanan) {
	$this->db->where('ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan_detail');
	$jumlah=$query->num_rows();

	// di additional
	$this->db->where('ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan_detail_additional');
	$jumlah=$jumlah+$query->num_rows();

	return $jumlah;
}

function getongkirpesanan($id_pesanan)  {
	$this->db->where('ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan');
	$result=$query->row();
	return $result->ONGKIR_PESANAN;
}

function gettotalpesanan($id_pesanan) {
	$this->db->where('ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan_detail');
	$result=$query->result();
	$total=0;
	foreach ($result AS $r) {
		$total=$total+($r->HARGA_PRODUK*$r->QTY_PRODUK);
	}
	return $total;
}

function gettotalpesananadditional($id_pesanan) {
	$this->db->where('ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan_detail_additional');
	$result=$query->result();
	$total=0;
	foreach ($result AS $r) {
		$total=$total+($r->HARGA_PRODUK*$r->QTY_PRODUK);
	}
	return $total;
}

function gettotalbayarpesanan($id_pesanan) {
	$ongkir=$this->getongkirpesanan($id_pesanan);

	$totalpesanan=$this->gettotalpesanan($id_pesanan);
	$totalpesananadditional=$this->gettotalpesananadditional($id_pesanan);

	$hasil=$ongkir+$totalpesananadditional+$totalpesanan;
	return $hasil;
}

function getdetailprodukpesanan($id_pesanan) {
	$this->db->select('a.*,b.NAMA_PRODUK,b.GAMBAR_PRODUK,b.MAX_ORDER_PRODUK');
	$this->db->where('a.ID_PESANAN',$id_pesanan);
	$this->db->join('produk b','a.ID_PRODUK=b.ID_PRODUK');
	$query=$this->db->get('pesanan_detail a');
	return $query->result();
}

function getdetailprodukpesananadditional($id_pesanan) {
	$this->db->where('a.ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan_detail_additional a');
	return $query->result();		
}

function gethargaproduk($id_produk) {
	$this->db->where('ID_PRODUK', $id_produk);
	$query=$this->db->get('produk');
	$row=$query->row();
	return $row->HARGA_PRODUK;
}

function updatestatususer($id_user) {
	$data['STATUS_USER']='1';
	$this->db->where('ID_USER',$id_user);
	$this->db->update('user',$data);
}

function cekfileexist($path_to_file) {	
	if (file_exists("../admin/".$path_to_file)) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function updateuser($update,$id_user) {
	$this->db->where('ID_USER',$id_user);
	$this->db->update('user',$update);
}

function getketeranganlokasipesanan($api_key,$lat,$long) {
	$json = file_get_contents("http://dev.virtualearth.net/REST/v1/Locations/".$lat.",".$long."?o=json&key=".$api_key);
	$data = json_decode($json, true);
	$namalokasi=$data['resourceSets'][0]['resources'][0]['name'];
	return $namalokasi;
}

function getDetailPesananHeader($id_pesanan) {
	$this->db->where('ID_PESANAN',$id_pesanan);
	$this->db->join('user','user.ID_USER=pesanan.ID_USER',"INNER");
	$query=$this->db->get('pesanan');
	return $query->row();
}

function updatestatuspesanan($id_user,$id_pesanan,$status) {
	$this->db->where('ID_USER',$id_user);
	$this->db->where('ID_PESANAN',$id_pesanan);
	if ($status=='2') {
		$data['STATUS_PESANAN']='1';
    } elseif ($status=='3') {
    	$data['STATUS_PESANAN']='x';
    }            
	$this->db->update('pesanan',$data);
}

function sendnotif($judul_notif,$body_notif,$id_user="ALL") {        
    $url = "https://fcm.googleapis.com/fcm/send";
    $token = "/topics/$id_user";
    $serverKey = 'AAAACLWTu94:APA91bFXNdxp-f6exoE_Xb_BH-bdYYBEklNWT-79LIAoJomad7861a2zEERao6xOFVkvIoyghX1Xg6h3oPpgj8j4astuDKr88i6Yx9ubkC-4YtUrs-0jNs_tJJC7DqSDm9ZbqU-95Il5';
    $title = $judul_notif;
    $body = $body_notif;
    $notification = array('title' =>$judul_notif , 'body' => $body_notif, 'sound' => 'default', 'badge' => '1');
    $arrayToSend = array('to' => $token, 'notification' => $notification,'priority'=>'high');
    $json = json_encode($arrayToSend);
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: key='. $serverKey;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    //Send the request
    $response = curl_exec($ch);
    //Close request
    if ($response === FALSE) {
        die('FCM Send Error: ' . curl_error($ch));
    }
    curl_close($ch);
  
}

function getlistprodukbysubkategori($id_sub_kategori,$keyword="",$paging=1) {
	$limit=$this->perpageproduk;
	if ($paging=="") { $page=0; } else { $page=$paging; }
    if ($page>0) {
        $offset=($limit*$page)-$limit;
    } else {
        $offset="0";
    }   
    $this->db->limit($limit, $offset);

	$this->db->where('ID_SUB_KATEGORI',$id_sub_kategori);
	$this->db->where('STATUS_PRODUK','1');
	$this->db->where('SHOW_PRODUK','1');
	if ($keyword!="") {
		$this->db->where(" (KODE_PRODUK LIKE '%$keyword%' OR NAMA_PRODUK LIKE '%$keyword%') ");
	}
	$query=$this->db->get('produk');
	return $query->result();
}

function getsubcategory($id_category) {
	$this->db->where('AKTIF','1');
	$this->db->where('ID_SUB_KATEGORI',$id_category);
	$query=$this->db->get('subkategori');
	return $query->result();
}

function cekopentoko($ID_SUB_KATEGORI) {
	$angkahari=date('N');
	$arrhari=array("","SENIN","SELASA","RABU","KAMIS","JUMAT","SABTU","MINGGU");
	$hari=$arrhari[$angkahari];
	$jamsekarang=date('H:i:s');		

	$this->db->where('ID_SUB_KATEGORI',$ID_SUB_KATEGORI);
	$this->db->where("BUKA_".$hari." <= ", $jamsekarang);
	$this->db->where("TUTUP_".$hari." >= ", $jamsekarang);
	$query=$this->db->get('subkategori');
	$row=$query->row();
	if ($row) {
		return TRUE;
	} else {
		return FALSE;
	}
}

}
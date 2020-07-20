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
	/*
	$this->db->where('NO_WHATSAPP_USER',$no_whatsapp);
	$query=$this->db->get('user');
	if ($query->num_rows()>0) {
		return TRUE;
	} else {
		return FALSE;
	}
	*/
	return $this->cekeksisnomorhpuserdankurir($no_whatsapp);	
}

function sendWhatsapp($nowhatsapp,$pesan) {
	// konversi nomor whtasapp
	if (substr($nowhatsapp,0,1)=='0') {
		$nowhatsapp=ltrim($nowhatsapp,'0');
		$nowhatsapp="+62".$nowhatsapp;
	}


	//$token = '941787fa6b9ac14062cc6d27eb0ae2a6be03456d98e5ef50';
	//$token = '38731a1f34d6ae11422174ae3290b52d28434bfa0d7a2950';
	$token   = '38731a1f34d6ae11422174ae3290b52d28434bfa0d7a2950';	
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
	$query	= "SELECT ID_TOKO, LAT_TOKO, LONG_TOKO, (6371 * 2 * ASIN(SQRT( POWER(SIN(( $lat_user - LAT_TOKO) *  pi()/180 / 2), 2) +COS( $lat_user * pi()/180) * COS(LAT_TOKO * pi()/180) * POWER(SIN(( $long_user - LONG_TOKO) * pi()/180 / 2), 2) ))) as distance  FROM toko  ORDER BY distance LIMIT 1";
	$query	= $this->db->query($query);
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

function getHargaBedaSubKategori() {
	$this->db->order_by('ID_SETTING','DESC');
	$query=$this->db->get('setting_harga');
	$row=$query->row();
	return $row->BEDA_SUBKATEGORI;
}

function getLastPesananUser($id_user) {
	$this->db->where('ID_USER',$id_user);
	$this->db->order_by('TANGGAL_PESANAN','DESC');
	$this->db->where('STATUS_KURIR !=','6');
	$query=$this->db->get('pesanan');
	return $query->row();
}

function getlistpesananuser($id_user,$show='active') {
	$this->db->where('ID_USER',$id_user);
	if ($show=='active') {
		$this->db->where('STATUS_KURIR !=','6');
	}
	$this->db->order_by('ID_PESANAN',"DESC");
	$query=$this->db->get('pesanan');
	return $query->result();
}

function getjumlahitempesanan($id_pesanan) {
	$jumlah=0;
	$this->db->select('QTY_PRODUK');
	$this->db->select_sum('QTY_PRODUK');
	$this->db->where('ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan_detail');
	$row=$query->row();
	$jumlah=$row->QTY_PRODUK;

	// di additional
	$this->db->select('QTY_PRODUK');
	$this->db->select_sum('QTY_PRODUK');
	$this->db->where('ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan_detail_additional');
	$row=$query->row();
	$jumlah=$jumlah+$row->QTY_PRODUK;

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

/* TAMBAHAN UNTUK MENGHITUNG TOTAL SELISIH */
function getselisihpesanan($id_pesanan) {

	$selisih = $this->db->where('ID_PESANAN', $id_pesanan)->get('pesanan_detail');
	$total = $this->db->select("SUM(SELISIH_PRODUK) as TOTAL")->where('ID_PESANAN', $id_pesanan)->get('pesanan_detail')->row();
	return $total->TOTAL;
	// return [
	// 	"total_pendapatan" => $total->TOTAL,
	// 	"detail_pesanan" => $selisih->result()
	// ];
}
/* END TAMBAHAN UNTUK MENGHITUNG TOTAL SELISIH */

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

	$totalbedasubkategori=$this->gettotalongkosbedasubkategori($id_pesanan);

	$hasil=$ongkir+$totalpesananadditional+$totalpesanan+$totalbedasubkategori;
	return $hasil;
}

function getdetailprodukpesanan($id_pesanan,$id_user="") {
	$this->db->select('a.*,b.NAMA_PRODUK,b.GAMBAR_PRODUK,b.MAX_ORDER_PRODUK,d.NAMA_SUB_KATEGORI');
	$this->db->where('a.ID_PESANAN',$id_pesanan);
	$this->db->join('produk b','a.ID_PRODUK=b.ID_PRODUK');
	$this->db->join('subkategori d','b.ID_SUB_KATEGORI=d.ID_SUB_KATEGORI');
	if ($id_user!="") {
		$this->db->join('pesanan c','a.ID_PESANAN=c.ID_PESANAN');
		$this->db->where('c.ID_USER',$id_user);
	}
	$query=$this->db->get('pesanan_detail a');
	return $query->result();
}

function getdetailprodukpesananadditional($id_pesanan,$id_user="") {
	$this->db->where('a.ID_PESANAN',$id_pesanan);
	if ($id_user!="") {
		$this->db->join('pesanan c','a.ID_PESANAN=c.ID_PESANAN');
		$this->db->where('c.ID_USER',$id_user);
	}
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
		$data['STATUS_KURIR']='2';
    } elseif ($status=='3') {
    	$data['STATUS_PESANAN']='x';
    	$data['STATUS_KURIR']='3';
    }            
	$this->db->update('pesanan',$data);
}

function sendnotif($judul_notif,$body_notif,$id_user="ALL") {        
    $params= array('judul_notif' => $judul_notif, 'body_notif' => $body_notif, 'id_user' => $id_user);
	$url="https://bakulemak.com/sendnotif.php";
	$post_string = http_build_query($params);
	$parts=parse_url($url);

	$fp = fsockopen($parts['host'],
		isset($parts['port'])?$parts['port']:80,
		$errno, $errstr, 30);

	if(!$fp)
	{
	    //Perform whatever logging you want to have happen b/c this call failed!    
	}
	$out = "POST ".$parts['path']." HTTP/1.1\r\n";
	$out.= "Host: ".$parts['host']."\r\n";
	$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
	$out.= "Content-Length: ".strlen($post_string)."\r\n";
	$out.= "Connection: Close\r\n\r\n";
	if (isset($post_string)) $out.= $post_string;

	fwrite($fp, $out);
	fclose($fp);	
}

function updatelokasipesanan($base_url_admin, $id_pesanan) {	
	$url=$base_url_admin."home/updatenamalokasipesanan/";
	
	$ch = curl_init();                
	$post['id_pesanan'] = $id_pesanan;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_POST, TRUE);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $post); 

	curl_setopt($ch, CURLOPT_USERAGENT, 'api');
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch,  CURLOPT_RETURNTRANSFER, false);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
	curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10); 

	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

	$data = curl_exec($ch);  

	curl_close($ch);
}

function curl_request_async($url, $id_pesanan)
{
    $ch = curl_init();                
	$post['id_pesanan'] = $id_pesanan;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_POST, TRUE);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $post); 

	curl_setopt($ch, CURLOPT_USERAGENT, 'api');
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch,  CURLOPT_RETURNTRANSFER, false);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
	curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10); 

	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

	$data = curl_exec($ch);  

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

function getsubcategory($id_category,$status='') {
	$angkahari=date('N');
	$arrhari=array("","SENIN","SELASA","RABU","KAMIS","JUMAT","SABTU","MINGGU");
	$hari=$arrhari[$angkahari];
	$jamsekarang=date('H:i:s');		
	
	if ($status=='buka') {							
		$this->db->where("BUKA_".$hari." <= ", $jamsekarang);
		$this->db->where("TUTUP_".$hari." >= ", $jamsekarang);
		$this->db->where("BUKA_".$hari." != ", "00:00:00");
		$this->db->where("TUTUP_".$hari." != ", "00:00:00");
	} elseif ($status=='tutup')	{
		$this->db->where("(BUKA_".$hari." > '$jamsekarang' OR TUTUP_".$hari." < '$jamsekarang' )");
		$this->db->where("BUKA_".$hari." != ", "00:00:00");
		$this->db->where("TUTUP_".$hari." != ", "00:00:00");
	} elseif ($status=='kapanpun') {
		$arrhari=array("","SENIN","SELASA","RABU","KAMIS","JUMAT","SABTU","MINGGU");
		for ($i=1; $i<count($arrhari);$i++) {
			$this->db->where('BUKA_'.$arrhari[$i],"00:00:00");
			$this->db->where('TUTUP_'.$arrhari[$i],"00:00:00");
		}
	}

	$this->db->where('AKTIF','1');
	$this->db->where('ID_KATEGORI',$id_category);
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

function getjumlahbedasubkategoripesanan($id_pesanan) {
	$this->db->distinct();
	$this->db->select('b.ID_SUB_KATEGORI');
	$this->db->where('a.ID_PESANAN',$id_pesanan);
	$this->db->join('produk b','a.ID_PRODUK=b.ID_PRODUK','INNER');
	
	$query=$this->db->get('pesanan_detail a');
	return $query->num_rows();
}

function updatehargabedasubkategori($id_pesanan,$hargabedasubkategori) {
	$this->db->where('id_pesanan',$id_pesanan);
	$data['ONGKOS_BEDA_SUB_KATEGORI']=$hargabedasubkategori;
	$this->db->update('pesanan',$data);
}

function gettotalongkosbedasubkategori($id_pesanan) {
	$jumlahbedasubkategori=$this->getjumlahbedasubkategoripesanan($id_pesanan);
	$biayabedasubkategori=$this->getHargaBedaSubKategori();
	if ($jumlahbedasubkategori>1) {
		return $biayabedasubkategori*($jumlahbedasubkategori-1);
	}else {
		return 0;
	}
}

function getlatestversion() {
	$this->db->order_by('ID_VERSION','DESC');
    $query=$this->db->get('version');
    $row=$query->row();
    return $row->LATEST_VERSION;
}

function cekalwaysopensubkategori($id_sub_kategori) {
    $this->db->where('ID_SUB_KATEGORI',$id_sub_kategori);
    $query=$this->db->get('subkategori');
    $row=$query->row();

    if (($row->BUKA_SENIN=='00:00:00')&&($row->TUTUP_SENIN=='00:00:00')&&($row->BUKA_SELASA=='00:00:00')&&($row->TUTUP_SELASA=='00:00:00')&&($row->BUKA_RABU=='00:00:00')&&($row->TUTUP_RABU=='00:00:00')&&($row->BUKA_KAMIS=='00:00:00')&&($row->TUTUP_KAMIS=='00:00:00')&&($row->BUKA_JUMAT=='00:00:00')&&($row->TUTUP_JUMAT=='00:00:00')&&($row->BUKA_SABTU=='00:00:00')&&($row->TUTUP_SABTU=='00:00:00')&&($row->BUKA_MINGGU=='00:00:00')&&($row->TUTUP_MINGGU=='00:00:00')) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function getidsubkategoriproduk($id_produk) {
	$this->db->where('ID_PRODUK',$id_produk);
	$query=$this->db->get('produk');
	$row=$query->row();
	return $row->ID_SUB_KATEGORI;
}

function getselisihproduk($id_produk) {
	$this->db->where('ID_PRODUK', $id_produk);
	$query=$this->db->get('produk');
	$row=$query->row();
	return $row->SELISIH_PRODUK;
}

//////////////////////////// UPDATE API UNTUK APP DRIVER   //////////////

function cektokenkurir($id_kurir,$token) {
	$this->db->where('a.ID_KURIR',$id_kurir);
	$this->db->where('a.TOKEN_KURIR',$token);
	$query=$this->db->get('kurir a');
	return $query->row();
}

function getdetailkurir($id_kurir) {
	$this->db->where('ID_KURIR',$id_kurir);
	$query=$this->db->get('kurir');
	$row=$query->row();
	return $row;
}

function getpesanansearching() {
	$this->db->where('STATUS_PESANAN','0');
	$this->db->where('STATUS_KURIR','4');
	$this->db->where('STATUS_KURIR !=','6');
	$this->db->order_by('TANGGAL_PESANAN','ASC');
	$query=$this->db->get('pesanan');
	return $query->result();
}

function getdetailuser($id_user) {
	$this->db->where('ID_USER',$id_user);
	$query=$this->db->get('user');
	return $query->row();
}

function getstatuskurirpesanan($id_pesanan) {
	$this->db->select('STATUS_KURIR');
	$this->db->where('ID_PESANAN',$id_pesanan);
	$query=$this->db->get('pesanan');
	$row=$query->row();
	return $row->STATUS_KURIR;
}

function cekakunkurir($id_user,$old_pass) {
	$this->db->where('ID_KURIR',$id_user);
	$this->db->where('PASSWORD_KURIR',md5($old_pass));
	$query=$this->db->get('kurir');
	return $query->row();
}

function gethistorypesanandriver($id_kurir,$month) {
	$this->db->where('ID_KURIR',$id_kurir);
	$this->db->where('STATUS_KURIR !=','6');
	$this->db->LIKE('TANGGAL_PESANAN',"$month");
	$this->db->order_by('TANGGAL_PESANAN','DESC');
	$query=$this->db->get('pesanan');
	return $query->result();
}

function cekeksisnomorhpuserdankurir($no_whatsapp) {
	$this->db->where('NO_WHATSAPP_USER',$no_whatsapp);
	$query=$this->db->get('user');
	if ($query->num_rows()>0) {
		return TRUE;
	} else {
		$this->db->where('NO_WHATSAPP_KURIR',$no_whatsapp);
		$query=$this->db->get('kurir');
		if ($query->num_rows()>0) {
			return TRUE;
		} else {
			return FALSE;
		}	
	}	
}

function cekeksistingnomorhpkurir($no_whatsapp) {
	return $this->cekeksisnomorhpuserdankurir($no_whatsapp);	
}

function cekstatuskurirsedangantarpesanan($id_kurir) {
	$this->db->where('ID_KURIR',$id_kurir);
	//$this->db->where('STATUS_KURIR','1');
	$this->db->where(" (STATUS_KURIR='1' OR STATUS_KURIR='5') ");
	$query=$this->db->get('pesanan');
	return $query->row();
}

function cetakjson($json) {	
	header('Content-Type: application/json');
	echo json_encode($json);
}

function cekpesananduplikat($id_user,$insert,$product) {
	$sama=FALSE;

	// syarat 1= header pesanan sama, syarat 2 = produk sama
	// cek apakah ada pesanan sama di 5 menit yg lalu
	$id_pesanan="";
	
	$waktulimamenitkebelakang = date('Y-m-d H:i:s', strtotime('-5 minutes'));  
    $this->db->order_by('TANGGAL_PESANAN','DESC');
    $this->db->where('ID_USER',$id_user);
    $this->db->where('LAT_PESANAN',$insert['LAT_PESANAN']);
    $this->db->where('LONG_PESANAN',$insert['LONG_PESANAN']);
    $this->db->where('LAT_MERCHANT',$insert['LAT_MERCHANT']);
    $this->db->where('TANGGAL_PESANAN >=',$waktulimamenitkebelakang);
    $query=$this->db->get('pesanan');
    $row=$query->row();
    if ($row) {
    	$syarat1=TRUE;
    	$id_pesanan=$row->ID_PESANAN;
    } else {
    	$syarat1=FALSE;
    }

    // cek produk
    $syarat2=FALSE;
    if ($syarat1==TRUE) {
    	// cek apakah semua produk sama
    	$semuaada=TRUE;
	    foreach ($product AS $p) {	    
	        $id_produk=$p->id;	        
	        $qty_produk=$p->qty;
	        if (!$this->cekeksisprodukpesanan($id_pesanan,$id_produk,$qty_produk)) {
	        	$semuaada=FALSE;
	        }
	    }

	    $syarat2=$semuaada;
	}    
    
    if (($syarat1==TRUE)&&($syarat2==TRUE)) {
    	$sama=TRUE;
    } else {
    	$sama=FALSE;
    }
    return $sama;
}

function cekeksisprodukpesanan($id_pesanan, $id_produk, $qty_produk) {
	$this->db->where('ID_PESANAN',$id_pesanan);
	$this->db->where('ID_PRODUK',$id_produk);
	$this->db->where('QTY_PRODUK',$qty_produk);
	$query=$this->db->get('pesanan_detail');
	return $query->row();
}

}
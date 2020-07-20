<?php

use Restserver\Libraries\REST_Controller;
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

// extends class dari REST_Controller
class Api extends REST_Controller {

    function __construct($config = 'rest') {
        parent::__construct($config);

        date_default_timezone_set('Asia/Singapore');
        $this->load->database();
        $this->load->model('mdl_api');
    }

    public $base_url_aplikasi="https://bakulemak.com/";
    public $base_url_admin = "https://bakulemak.com/admin/";
    public $bing_api_key="AvK0oEN64-bBe6_XAb5HFvS1P91hCKD5yo2DXCEJ_NqXrbbgljxTPJVS3JbECQQm";

    //Menampilkan data kontak
    function index_post() {
       /* $id = $this->post('id');
        if ($id == '') {
            $kontak = $this->db->get('promo')->result();
        } else {
            $this->db->where('id_promo', $id);
            $kontak = $this->db->get('promo')->result();
        }
        $this->response($kontak, 200);*/
    }

    //Masukan function selanjutnya disini
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

    function login_post() {
        $whatsapp=$this->post('whatsapp');
        $password=md5($this->post('password'));

        $this->db->where('NO_WHATSAPP_USER',$whatsapp);
        $this->db->where('PASSWORD_USER',$password);
        $query=$this->db->get('user');
        $user=$query->row();
        if ($user) {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");
            $temp=array(
                'id' => $user->ID_USER,
                'nama' => $user->NAMA_USER,
                'whatsapp' => $user->NO_WHATSAPP_USER,
                'token' => $user->TOKEN_USER,
                'created_at' => $this->konversitanggal('d F Y',$user->DATE_ADD)
            );
            $data['data']=$temp;
            $this->mdl_api->updatestatususer($user->ID_USER);            
        } else {
            $data['message']='Gagal. Password/nomor whatsapp salah atau belum terdaftar';
            $data['status']="500";
            $data['success']=false;
        }
        $this->response($data);
    }

    function validateUser_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');

        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data['status']='200';
            $data['messages']="Success";
            $data['success']=true;
            $user=$this->mdl_api->getUserDetail($id_user);
            $temp=array(
                'id' => $user->ID_USER,
                'nama' => $user->NAMA_USER,
                'whatsapp' => $user->NO_WHATSAPP_USER,
                'token' => $user->TOKEN_USER,
                'created_at' => $this->konversitanggal('d F Y',$user->DATE_ADD)
            );
            $data['data']=$temp;
        }
        $this->response($data);
    }

    function register_post() {
        $nama=$this->post('name');
        $whatsapp=$this->post('whatsapp');
        $imei=$this->post('imei');

        $response=$this->mdl_api->registerUser($nama,$whatsapp,$imei);
        if (strtolower($response)=="success") {
            $data['status']='200';
            $data['success']=true;
            $data['message']="Selamat bergabung, password telah kami kirimkan ke whatsapp Anda";
        } elseif (strtolower($response)=='phone_offline') {
            $data['status']='500';
            $data['success']=false;
            $data['message']="Whatsapp server error. Mohon coba beberapa saat lagi";
        } else {
            $data['status']='500';
            $data['success']=false;
            $data['message']="Nomor whatsapp tidak ditemukan";
        }    
        $this->response($data);
    }

    function promo_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');

        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");
            $promo=$this->mdl_api->getPromo();            
            $temp=array(); $temp2=array();
            foreach ($promo AS $p) {
                $temp['id']=$p->ID_PROMO;
                $temp['image']=$this->base_url_admin."".$p->GAMBAR_PROMO;
                $temp['description']=$p->KETERANGAN_PROMO;
                $temp2[]=$temp;
            }

            $data['data']=$temp2;
        }
        $this->response($data);
    }

    function category_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');

        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");
            $kategori=$this->mdl_api->getKategori();            
            $temp=array(); $temp2=array();
            foreach ($kategori AS $k) {
                $temp['id']=$k->ID_KATEGORI;
                $temp['image']=$this->base_url_admin."".$k->GAMBAR_KATEGORI;
                $temp['name']=$k->NAMA_KATEGORI;
                $temp['item']=$this->mdl_api->getjumlahprodukkategori($k->ID_KATEGORI);
                $temp2[]=$temp;
            }

            $data['data']=$temp2;
        }
        $this->response($data);
    }

    function product_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');
        $page=$this->post('page');

        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");
            $data['page']=$page;                        

            $id_kategori=$this->post('id_category');
            $keyword=$this->post('keyword');

            // for next page            
            $nextpageproduk=$this->mdl_api->getlistproduk($id_kategori,$keyword,($page+1));
            if ($nextpageproduk) {
                $data['isLast']=FALSE;    
            } else {
                $data['isLast']=TRUE;    
            }            

            $produk=$this->mdl_api->getlistproduk($id_kategori,$keyword,$page);
            $temp2=array();
            foreach ($produk AS $p) {
                $temp['id']=$p->ID_PRODUK;                
                $temp['name']=$p->NAMA_PRODUK;
                $temp['price']=$p->HARGA_PRODUK;
                $temp['max']=$p->MAX_ORDER_PRODUK;
                $temp['new']=$this->mdl_api->cekstatusnewprodukuser($p->ID_PRODUK,$id_user);
                
                if ($this->mdl_api->cekfileexist($p->GAMBAR_PRODUK)) {
                    $temp['image']=$this->base_url_admin."".$p->GAMBAR_PRODUK;                
                } else {
                    $temp['image']=$this->base_url_admin."assets/images/product-icon.jpg";
                }
            

                $temp2[]=$temp;
            }
            $data['data']=$temp2;

            

        }
        $this->response($data);
    }

    function nearestMerchant_post() {
        //SELECT toko.ID_TOKO, (6371 * 2 * ASIN(SQRT( POWER(SIN(( 0.75 - LAT_TOKO) *  pi()/180 / 2), 2) +COS( 0.75 * pi()/180) * COS(LAT_TOKO * pi()/180) * POWER(SIN(( 100 - LONG_TOKO) * pi()/180 / 2), 2) ))) as distance  from toko order by distance
        $token=$this->post('token');
        $id_user=$this->post('id_user');

        $lat_user=$this->post('lat');
        $long_user=$this->post('lon');
        $tokoterdekat=$this->mdl_api->gettokoterdekat($lat_user,$long_user);

        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } elseif ((!$tokoterdekat)||($tokoterdekat->distance>9)) {
            $data['status']='404';
            $data['message']='Ups… mohon maaf, lokasi anda belum masuk dalam cakupan layanan kami.';
        } else {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");

            $temp['id']=$tokoterdekat->ID_TOKO;
            $temp['merchant_lat']=$tokoterdekat->LAT_TOKO;
            $temp['merchant_lon']=$tokoterdekat->LONG_TOKO;
            $temp['user_lat']=$lat_user;
            $temp['user_lon']=$long_user;
            $temp['price']=$this->mdl_api->hitungbiayaantar($tokoterdekat->distance);
            $data['data']=$temp;
        }
        $this->response($data);
    }

    /*
    function lastPoint_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');
        
        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");

            $lastpesanan=$this->mdl_api->getLastPesananUser($id_user);
            if ($lastpesanan) {
                $temp['user_lat']=$lastpesanan->LAT_PESANAN;
                $temp['user_lon']=$lastpesanan->LONG_PESANAN;
                $temp['merchant_lat']=$lastpesanan->LAT_MERCHANT;
                $temp['merchant_lon']=$lastpesanan->LONG_MERCHANT;
                $temp['price']=$lastpesanan->ONGKIR_PESANAN;
                $data['data']=$temp;
            }  else {
                $data['data']=null;
            }         
        }
        $this->response($data);
    }*/

    function history_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');
        
        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");

            $pesanan=$this->mdl_api->getlistpesananuser($id_user);
            foreach ($pesanan AS $p) {
                $temp['id_history']=$p->ID_PESANAN;
                $temp['date']=$this->konversitanggal('d M Y',$p->TANGGAL_PESANAN);
                $temp['qty']=$this->mdl_api->getjumlahitempesanan($p->ID_PESANAN);
                $temp['time']=$this->konversitanggal('H:i',$p->TANGGAL_PESANAN);
                $temp['price']=$this->mdl_api->gettotalbayarpesanan($p->ID_PESANAN);
                if ($p->STATUS_PESANAN=='0') {
                    $temp['status']='1';
                } elseif ($p->STATUS_PESANAN=='1') {
                    $temp['status']='2';
                } elseif ($p->STATUS_PESANAN=='x') {
                    $temp['status']='3';
                }
                $temp2[]=$temp;
            }
            $data['data']=$temp2;
        }
        $this->response($data);
    }

    function historyDetail_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');
        $id_pesanan=$this->post('id_history');
        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } else {
            $status=$this->mdl_api->getSuccessResponseVariable("Success");
            $response=$status;

            

            $produkpesanan=$this->mdl_api->getdetailprodukpesanan($id_pesanan);
            foreach ($produkpesanan AS $pp) {
                $temp['id']=$pp->ID_PRODUK;
                $temp['name']=$pp->NAMA_PRODUK;
                $temp['image']=$this->base_url_admin."".$pp->GAMBAR_PRODUK;
                $temp['price']=$pp->HARGA_PRODUK;
                $temp['qty']=$pp->QTY_PRODUK;
                $temp['max']=$pp->MAX_ORDER_PRODUK;
                $produk[]=$temp;
            }
            
            // additional
            $produkpesananaadditional=$this->mdl_api->getdetailprodukpesananadditional($id_pesanan);
            foreach ($produkpesananaadditional AS $ppa) {
                $temp['id']=$ppa->ID_PRODUK;
                $temp['name']=$ppa->NAMA_PRODUK;
                $temp['image']=$this->base_url_admin."assets/images/product-icon.jpg";
                $temp['price']=$ppa->HARGA_PRODUK;
                $temp['qty']=$ppa->QTY_PRODUK;
                $produk[]=$temp;
            }

            $pesananheader=$this->mdl_api->getDetailPesananHeader($id_pesanan);
            $temproduk['address']=$pesananheader->LOKASI_PESANAN;
            $temproduk['ongkir']=$pesananheader->ONGKIR_PESANAN+$pesananheader->ONGKOS_BEDA_SUB_KATEGORI;
            $temproduk['catatan']=$pesananheader->CATATAN_PESANAN;
            $temproduk['produk']=$produk;
            $temproduk['price']=$this->mdl_api->gettotalbayarpesanan($id_pesanan);
            $response['data']=$temproduk;
        }
        $this->response($response);
    }

    function checkout_post() {
        $inputan=$this->post('data');
        $hasil=json_decode($inputan);
        $id_user=$hasil->id_user;
        $token=$hasil->token;
        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } else {
            if (empty($hasil->product)) {
                $response['status']='403';
                $response['message']='Produk belum dipilih';
            } else {
                $status=$this->mdl_api->getSuccessResponseVariable("Success");
                $response=$status;

                $insert['TANGGAL_PESANAN']=date("Y-m-d H:i:s");
                $insert['ID_USER']=$id_user;
                $insert['CATATAN_PESANAN']=$hasil->catatan;

                $location_user=$hasil->location;            
                $insert['LAT_PESANAN']=$location_user->lat;
                $insert['LONG_PESANAN']=$location_user->lon;                

                $tokoterdekat=$this->mdl_api->gettokoterdekat($location_user->lat,$location_user->lon);
                $insert['LAT_MERCHANT']=$tokoterdekat->LAT_TOKO;
                $insert['LONG_MERCHANT']=$tokoterdekat->LONG_TOKO;
                $insert['ONGKIR_PESANAN']=$this->mdl_api->hitungbiayaantar($tokoterdekat->distance);

                $insert['LOKASI_PESANAN']=$this->mdl_api->getketeranganlokasipesanan($this->bing_api_key,$insert['LAT_PESANAN'],$insert['LONG_PESANAN']);
                
                $this->db->insert('pesanan',$insert);
                $id_pesanan=$this->db->insert_id();

                //// produk
                $product=$hasil->product;   
                foreach ($product AS $p) {                
                    $pesanandetail['ID_PESANAN']=$id_pesanan;
                    $pesanandetail['ID_PRODUK']=$p->id;
                    $pesanandetail['HARGA_PRODUK']=$this->mdl_api->gethargaproduk($p->id);
                    $pesanandetail['QTY_PRODUK']=$p->qty;
                    $this->db->insert('pesanan_detail',$pesanandetail);
                }

                // cek beda subkategori
                $jumlahsubkategori=$this->mdl_api-> getjumlahbedasubkategoripesanan($id_pesanan);
                if ($jumlahsubkategori>1) {
                    $hargabedasubkategori=($jumlahsubkategori-1)*($this->mdl_api->getHargaBedaSubKategori());
                    $this->mdl_api->updatehargabedasubkategori($id_pesanan,$hargabedasubkategori);
                }

                $produkpesanan=$this->mdl_api->getdetailprodukpesanan($id_pesanan);
                foreach ($produkpesanan AS $pp) {
                    $temp['id']=$pp->ID_PRODUK;
                    $temp['name']=$pp->NAMA_PRODUK;
                    $temp['image']=$this->base_url_admin."".$pp->GAMBAR_PRODUK;
                    $temp['price']=$pp->HARGA_PRODUK;
                    $temp['qty']=$pp->QTY_PRODUK;
                    $temp['max']=$pp->MAX_ORDER_PRODUK;
                    $produk[]=$temp;
                }
                // additional
                $produkpesananaadditional=$this->mdl_api->getdetailprodukpesananadditional($id_pesanan);
                foreach ($produkpesanan AS $ppa) {
                    $temp['id']=$ppa->ID_PRODUK;
                    $temp['name']=$ppa->NAMA_PRODUK;
                    $temp['image']=$this->base_url_admin."assets/images/product-icon.jpg";
                    $temp['price']=$ppa->HARGA_PRODUK;
                    $temp['qty']=$ppa->QTY_PRODUK;
                    $produk[]=$temp;
                }

                $temproduk['produk']=$produk;
                $temproduk['price']=$this->mdl_api->gettotalbayarpesanan($id_pesanan);
                $response['data']=$temproduk;
                
            }    
        }

        $this->response($response);
    }

    function editProfile_post() {
        $id_user=$this->post('id_user');
        $token=$this->post('token');
        $old_password=$this->post('old_pass');
        $new_password=$this->post('new_pass');
        $nama=$this->post('nama');
        $no_whatsapp=$this->post('no_whatsapp');
        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } elseif (($old_password=="")||($nama=="")||($new_password=="")||($no_whatsapp=="")) {
            $response['status']='500';
            $response['message']='Mohon lengkapi data';
        } else {
            // cek login antara id_user dan old_password
            $this->db->where('ID_USER',$id_user);    
            $this->db->where('PASSWORD_USER',MD5($old_password));
            $query=$this->db->get('user');
            $user=$query->row();
            if (!$user) {
                $response['status']='500';
                $response['message']='Password lama salah';
            } else {
                if ($no_whatsapp!=$user->NO_WHATSAPP_USER) {
                    // artinya ada penggantian nomor whatsapp
                    // cek eksisting nomor hp 
                    $eksisnomorhp=$this->mdl_api->cekeksistingnomorhp($no_whatsapp);
                    if ($eksisnomorhp) {
                        $response['status']='500';
                        $response['message']='Nomor whatsapp sudah digunakan pengguna lain';
                    } else {
                        $passwordbaru=$this->mdl_api->generateRandomString(5);
                        $pesan="Anda telah meminta melakukan permintaan penggantian nomor whatsapp. \nPassword baru anda adalah : *$passwordbaru* \nSilahkan login di aplikasi dengan memasukkan nomor whatsapp baru dan password anda. Terima kasih.";
                        $warespon=$this->mdl_api->sendWhatsapp($no_whatsapp,$pesan);
                        if (strtolower($warespon)=='success') {
                            $update['NAMA_USER']=$nama;
                            $update['NO_WHATSAPP_USER']=$no_whatsapp;
                            $update['PASSWORD_USER']=md5($new_password);                            
                            $this->mdl_api->updateuser($update,$id_user);

                            $response['status']='200';
                            $response['messages']="Success";
                            $response['success']=true;
                            $user=$this->mdl_api->getUserDetail($id_user);
                            $temp=array(
                                'id' => $user->ID_USER,
                                'nama' => $user->NAMA_USER,
                                'whatsapp' => $user->NO_WHATSAPP_USER,
                                'token' => $user->TOKEN_USER,
                                'created_at' => $this->konversitanggal('d F Y',$user->DATE_ADD)
                            );
                            $response['data']=$temp;                    
                        } else {
                            $response['status']='500';
                            $response['message']="Whatsapp server error. Mohon dicoba beberapa saat lagi.";
                        }
                    }                    
                } else {
                    // nomor whatsapp tetap, hanya mengganti password/nama
                    $update['NAMA_USER']=$nama;
                    $update['PASSWORD_USER']=md5($new_password);
                    $this->mdl_api->updateuser($update,$id_user);
                    
                    $response['status']='200';
                    $response['messages']="Success";
                    $response['success']=true;
                    $user=$this->mdl_api->getUserDetail($id_user);
                    $temp=array(
                        'id' => $user->ID_USER,
                        'nama' => $user->NAMA_USER,
                        'whatsapp' => $user->NO_WHATSAPP_USER,
                        'token' => $user->TOKEN_USER,
                        'created_at' => $this->konversitanggal('d F Y',$user->DATE_ADD)
                    );
                    $response['data']=$temp;      
                }
            }
        }

        $this->response($response);
    }

    function testkirimwa_post() {
        $nomorhp=$this->post('nomorhp');
        $message=$this->post('message');
        $data['response']=$this->mdl_api->sendWhatsapp($nomorhp,$message);
        $this->response($data);
    }

    function testgetlokasipesanan_post() {        
        $insert['LAT_PESANAN']="-3.934";
        $insert['LONG_PESANAN']="115.0649983";
        $lokasi=$this->mdl_api->getketeranganlokasipesanan($this->bing_api_key,$insert['LAT_PESANAN'],$insert['LONG_PESANAN']);
        $response['lokasi']=$lokasi;
        $this->response($response);

    }

    function testkirimnotif_post() {
        $judul_notif="Test";
        $body_notif="This notif sent to all user BakulEmak";
        $this->mdl_api->sendnotif($judul_notif,$body_notif);
    }

    function updateStatus_post() {
    	$token=$this->post('token');
        $id_user=$this->post('id_user');
        $id_pesanan=$this->post('id_history');
        $status=$this->post('status');
        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } else {
        	$this->mdl_api->updatestatuspesanan($id_user,$id_pesanan,$status);
            $res=$this->mdl_api->getSuccessResponseVariable("Success");
            $response=$res;
        }
        $this->response($response);
    }

    function productBySubcategory_post() {
        $id_user=$this->post('id_user');
        $token=$this->post('token');
        $id_subcategory=$this->post('id_subcategory');
        $keyword=$this->post('keyword');
        $page=$this->post('page');

        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");
            $data['page']=$page;                        
            
            // for next page            
            $nextpageproduk=$this->mdl_api->getlistprodukbysubkategori($id_subcategory,$keyword,($page+1));
            if ($nextpageproduk) {
                $data['isLast']=FALSE;    
            } else {
                $data['isLast']=TRUE;    
            }            

            $produk=$this->mdl_api->getlistprodukbysubkategori($id_subcategory,$keyword,$page);
            $temp2=array();
            foreach ($produk AS $p) {
                $temp['id']=$p->ID_PRODUK;                
                $temp['name']=$p->NAMA_PRODUK;
                $temp['price']=$p->HARGA_PRODUK;
                $temp['max']=$p->MAX_ORDER_PRODUK;
                $temp['new']=$this->mdl_api->cekstatusnewprodukuser($p->ID_PRODUK,$id_user);
                
                if ($this->mdl_api->cekfileexist($p->GAMBAR_PRODUK)) {
                    $temp['image']=$this->base_url_admin."".$p->GAMBAR_PRODUK;                
                } else {
                    $temp['image']=$this->base_url_admin."assets/images/product-icon.jpg";
                }
            

                $temp2[]=$temp;
            }
            $data['data']=$temp2;

            

        }
        $this->response($data);
    }

    function subcategory_post() {
        $id_user=$this->post('id_user');
        $token=$this->post('token');
        $id_category=$this->post('id_category');
        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");

            $temp2=array(); $temp=array();

            // cek yang selal buka 
            $subcategory=$this->mdl_api->getsubcategory($id_category,'kapanpun');            
            foreach ($subcategory AS $s) {
                $temp['id']=$s->ID_SUB_KATEGORI;
                $temp['image']=$this->base_url_admin.$s->GAMBAR_SUB_KATEGORI;
                $temp['name']=$s->NAMA_SUB_KATEGORI;
                $temp['isAlwaysOpen']=$this->mdl_api->cekalwaysopensubkategori($s->ID_SUB_KATEGORI);
                if ($temp['isAlwaysOpen']==TRUE) {
                    $temp['isOpen']=TRUE;
                } else {
                    $temp['isOpen']=$this->mdl_api->cekopentoko($s->ID_SUB_KATEGORI);    
                }                

                $temp2[]=$temp;
            }

            // cek yang buka 
            $subcategory=$this->mdl_api->getsubcategory($id_category,'buka');
            foreach ($subcategory AS $s) {
                $temp['id']=$s->ID_SUB_KATEGORI;
                $temp['image']=$this->base_url_admin.$s->GAMBAR_SUB_KATEGORI;
                $temp['name']=$s->NAMA_SUB_KATEGORI;
                $temp['isAlwaysOpen']=$this->mdl_api->cekalwaysopensubkategori($s->ID_SUB_KATEGORI);
                if ($temp['isAlwaysOpen']==TRUE) {
                    $temp['isOpen']=TRUE;
                } else {
                    $temp['isOpen']=$this->mdl_api->cekopentoko($s->ID_SUB_KATEGORI);    
                }                

                $temp2[]=$temp;
            }

            // yang tutup
            $subcategory=$this->mdl_api->getsubcategory($id_category,'tutup');
            foreach ($subcategory AS $s) {
                $temp['id']=$s->ID_SUB_KATEGORI;
                $temp['image']=$this->base_url_admin.$s->GAMBAR_SUB_KATEGORI;
                $temp['name']=$s->NAMA_SUB_KATEGORI;
                $temp['isAlwaysOpen']=$this->mdl_api->cekalwaysopensubkategori($s->ID_SUB_KATEGORI);
                if ($temp['isAlwaysOpen']==TRUE) {
                    $temp['isOpen']=TRUE;
                } else {
                    $temp['isOpen']=$this->mdl_api->cekopentoko($s->ID_SUB_KATEGORI);    
                }                

                $temp2[]=$temp;
            }

            $data['data']=$temp2;
        }
        $this->response($data);
    }

    function latestVersion_get() {        
        $data['data']=$this->mdl_api->getlatestversion();
        $this->response($data);
    }
}
?>
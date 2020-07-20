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
        $this->load->helper('string');
        $this->load->model('mdl_api');
        $this->load->model('mdl_merchant');
        $this->load->model('mdl_product');
        $this->load->model('mdl_price_rates');
    }

    public $base_url_aplikasi   = "https://bakulemak.com/";
    public $base_url_admin      = "https://bakulemak.com/admin/";            
    public $bing_api_key        = "AvK0oEN64-bBe6_XAb5HFvS1P91hCKD5yo2DXCEJ_NqXrbbgljxTPJVS3JbECQQm";

    // status pesanan
    /*
    const val ONPROGRESS = 1 /Pesanan sudah diambil oleh driver dan dalam proses pemesanan/
    const val DONE = 2 /Pesanan sudah selesai/ >> HIJAU (1 jika di database)
    const val FAILED = 3 /Pesanan dibatalkan/ >> MERAH (x jika di database)
    const val DRIVER_SEARCHING = 4 /Pesanan sudah di checkout oleh user tapi sedang mencari driver/ >> ABU-ABU (0 jika di database dan status_kurir)
    const val ON_DELIVERY = 5 /Pesanan sedang diantar oleh driver/
    
    4  -> 1 -> 5 -> 2
               \ -> 3
    */


    //Menampilkan data kontak
    function index_post() {
 
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
        // aktifkan jika sudah release versi baru //        
       
        $data['status']='500';
        $data['message']='Update sekarang aplikasi Bakul Emak kamu di Google Play. Ada fitur baru loh...';
        $this->response($data);
        die();
        /*
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
        */
    }

    function nearestMerchantNew_post() {  
        $inputan=$this->post('data');
        $hasil=json_decode($inputan);
        $id_user=$hasil->id_user;
        $token=$hasil->token;
        if (!$this->mdl_api->cektokenuser($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $location_user=$hasil->location;            
            $tokoterdekat=$this->mdl_api->gettokoterdekat($location_user->lat,$location_user->lon);
            if ((!$tokoterdekat)||($tokoterdekat->distance>9)) {
                $data['status']='404';
                $data['message']='Ups… mohon maaf, lokasi anda belum masuk dalam cakupan layanan kami.';
            } elseif (empty($hasil->product)) {
                $data['status']='403';
                $data['message']='Produk belum dipilih';
            } else {
                $data=$this->mdl_api->getSuccessResponseVariable("Success");

                $temp['id']=$tokoterdekat->ID_TOKO;
                $temp['merchant_lat']=$tokoterdekat->LAT_TOKO;
                $temp['merchant_lon']=$tokoterdekat->LONG_TOKO;
                $temp['user_lat']=$location_user->lat;
                $temp['user_lon']=$location_user->lon;
                $temp['price']=$this->mdl_api->hitungbiayaantar($tokoterdekat->distance);

                //////////                
                //// produk
                $product=$hasil->product;   
                $arridsubkategori=array();
                foreach ($product AS $p) {     
                    /*           
                    $pesanandetail['ID_PRODUK']=$p->id;
                    $pesanandetail['HARGA_PRODUK']=$this->mdl_api->gethargaproduk($p->id);
                    $pesanandetail['QTY_PRODUK']=$p->qty;
                    $this->db->insert('pesanan_detail',$pesanandetail);
                    */

                    $id_subkategori=$this->mdl_api->getidsubkategoriproduk($p->id);
                    if (!in_array($id_subkategori, $arridsubkategori)) {
                        $arridsubkategori[]=$id_subkategori;
                    }
                }

                // cek beda subkategori
                if (count($arridsubkategori)>1) {
                    // jumlahkan beda subkategori
                    $hargabedasubkategori=$this->mdl_api->getHargaBedaSubKategori();
                    $temp['price']=$temp['price']+($hargabedasubkategori*(count($arridsubkategori)-1));
                }

                $data['data']=$temp;
                
            }    
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

            $pesanan=$this->mdl_api->getlistpesananuser($id_user);//
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
            $produk=array();
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
            //$temproduk['price']=$this->mdl_api->gettotalbayarpesanan($id_pesanan);
            $temproduk['price']=$this->mdl_api->gettotalpesanan($id_pesanan)+$this->mdl_api->gettotalpesananadditional($id_pesanan);
            $temproduk['status']=$pesananheader->STATUS_KURIR;            
            $user['lat']=$pesananheader->LAT_PESANAN;
            $user['lon']=$pesananheader->LONG_PESANAN;
            $temproduk['user']=$user;

            // get kurir dan merchant
            if ($pesananheader->ID_KURIR!='0') {
            	// get detail kurir
            	$detailkurir=$this->mdl_api->getdetailkurir($pesananheader->ID_KURIR);
            	$kurir['name']=$detailkurir->NAMA_KURIR;
            	$kurir['phone']=$detailkurir->NO_WHATSAPP_KURIR;
            	$kurir['last_lat']=$detailkurir->LAT_KURIR_SEKARANG;
            	$kurir['last_lon']=$detailkurir->LONG_KURIR_SEKARANG;
            	$kurir['image']=$this->base_url_admin.$detailkurir->FOTO_KURIR;

            	$merchant['lat']=$pesananheader->LAT_MERCHANT;
            	$merchant['lon']=$pesananheader->LONG_MERCHANT;

            	$temproduk['driver']=$kurir;
            	$temproduk['merchant']=$merchant;
            } else {
            	$temproduk['driver']=NULL;
            	$temproduk['merchant']=NULL;
            }
            
            $response['data']=$temproduk;
        }
        $this->response($response);
    }

    function checkout_post() {
        // aktifkan jika sudah release versi baru //    
           
        $data['status']='500';
        $data['message']='Update sekarang aplikasi Bakul Emak kamu di Google Play. Ada fitur baru loh...';
        $this->response($data);
        die();
        /*
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
        */
    }

    function checkoutNew_post() {
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
                //if ($jumlahsubkategori>1) {
                $hargabedasubkategori=($jumlahsubkategori-1)*($this->mdl_api->getHargaBedaSubKategori());
                $this->mdl_api->updatehargabedasubkategori($id_pesanan,$hargabedasubkategori);
                //}

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
        $data['nomorhp']=$nomorhp;
        $data['message']=$message;
        $data['response']=$this->mdl_api->sendWhatsapp($nomorhp,$message);
        $this->response($data);
    }

    function testgetlokasipesanan_post() {        
        $id_pesanan="747";
        //$this->mdl_api->updatelokasipesanan($this->base_url_admin, $id_pesanan);      
        //file_get_contents($this->base_url_admin.'home/updatenamalokasipesanan/'.$id_pesanan);        
        //$this->mdl_api->curl_request_async($this->base_url_admin.'home/updatenamalokasipesanan/', $id_pesanan);
        $this->mdl_api->updatelokasipesanan($this->base_url_admin.'home/updatenamalokasipesanan/', $id_pesanan);   
    }

    function testkirimnotif_post() {
        $judul_notif="Test";
        $body_notif="This notif sent to all user BakulEmak";
        $this->mdl_api->sendnotif($judul_notif,$body_notif,'6');
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

    /////////////////////////////////////////////////////////////////////////////
    /////////////// UPDATE API UNTUK DRIVER /////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////
    function driverLogin_post() {
    	$whatsapp=$this->post('whatsapp');
    	$password=md5($this->post('password'));

    	$this->db->where('NO_WHATSAPP_KURIR',$whatsapp);
        $this->db->where('PASSWORD_KURIR',$password);
        $this->db->where('SHOW_KURIR','1');
        $query=$this->db->get('kurir');
        $user=$query->row();
        if ($user) {
            $data=$this->mdl_api->getSuccessResponseVariable("Success");
            $temp=array(
                'id' => $user->ID_KURIR,
                'name' => $user->NAMA_KURIR,
                'whatsapp' => $user->NO_WHATSAPP_KURIR,
                'token' => $user->TOKEN_KURIR,
                'created_at' => $this->konversitanggal('d F Y',$user->DATE_ADD),
                'image' => $this->base_url_admin.$user->FOTO_KURIR
            );
            $data['data']=$temp;          
        } else {
            $data['message']='Gagal. Password/nomor whatsapp salah atau belum terdaftar di sistem';
            $data['status']="500";
            $data['success']=false;
        }
        $this->response($data);
    }

    function checkoutToDriver_post() {
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

                //$insert['LOKASI_PESANAN']=$this->mdl_api->getketeranganlokasipesanan($this->bing_api_key,$insert['LAT_PESANAN'],$insert['LONG_PESANAN']);
                $insert['STATUS_KURIR']="4";
                // cek apakah pesanan duplikat 
                $product=$hasil->product;   
                if ($this->mdl_api->cekpesananduplikat($id_user,$insert,$product)) {
                	$response['status']='500';
                	$response['message']='Pesanan anda sedang diproses. Mohon tunggu beberapa saat untuk memesan kembali.';
                	$this->response($response);
                	die();
                }

                $this->db->insert('pesanan',$insert);
                $id_pesanan=$this->db->insert_id();

                             
                $id_pesanan=$this->db->insert_id();                                

                $temproduk['order_number']=$id_pesanan;        
                $temproduk['status']=$insert['STATUS_KURIR'];                

                //// produk                
                foreach ($product AS $p) {                
                    $pesanandetail['ID_PESANAN']=$id_pesanan;
                    $pesanandetail['ID_PRODUK']=$p->id;
                    $pesanandetail['HARGA_PRODUK']=$this->mdl_api->gethargaproduk($p->id);
                    $pesanandetail['SELISIH_PRODUK']=$this->mdl_api->getselisihproduk($p->id);
                    $pesanandetail['QTY_PRODUK']=$p->qty;
                    $this->db->insert('pesanan_detail',$pesanandetail);
                }

                // cek beda subkategori
                $jumlahsubkategori=$this->mdl_api->getjumlahbedasubkategoripesanan($id_pesanan);
                //if ($jumlahsubkategori>1) {
                $hargabedasubkategori=($jumlahsubkategori-1)*($this->mdl_api->getHargaBedaSubKategori());
                $this->mdl_api->updatehargabedasubkategori($id_pesanan,$hargabedasubkategori);
                //}

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

                $temproduk['price']=$this->mdl_api->gettotalbayarpesanan($id_pesanan);
                $temproduk['produk']=$produk;
                $response['data']=$temproduk;         

                // update lokasi async   
                $this->mdl_api->updatelokasipesanan($this->base_url_admin.'home/updatenamalokasipesanan/', $id_pesanan);   
            }    
        }

        $this->response($response);
        // $this->mdl_api->cetakjson($response);
        
        // $judul_notif="Pesanan baru telah diterima";
        // $body_notif="Silahkan cek aplikasi kurir BakulEmak";
        // $this->mdl_api->sendnotif($judul_notif,$body_notif,"Driver");
    }

    function checkoutStatus_post() {
    	$user_id=$this->post('user_id');
        $token=$this->post('token');
        $judul_notif="";
        if (!$this->mdl_api->cektokenuser($user_id,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } else {
        	$status=$this->mdl_api->getSuccessResponseVariable("Success");
            $response=$status;

        	$order_id=$this->post('order_id');
        	$this->db->where('ID_USER',$user_id);
        	$this->db->where('ID_PESANAN',$order_id);
        	$query=$this->db->get('pesanan');
        	$row=$query->row();        	
        	
        	if (!$row) {
                $response['status']='500';
                $response['message']="Pesanan tidak ditemukan.";
                $response['success']=false;
            } else {
                $data['order_number']=$row->ID_PESANAN;
                $data['status']=$row->STATUS_KURIR;
                if ($row->ID_KURIR=='0') {
                    $data['driver_id']=NULL;
                } else {
                    $data['driver_id']=$row->ID_KURIR;
                }
                

                $minutes_to_add=10;
                $time = new DateTime($row->TANGGAL_PESANAN);
                $time->add(new DateInterval('PT' . $minutes_to_add . 'M'));
                $waktusepuluhmenitkedepan = $time->format('Y-m-d H:i');                
                if ((date("Y-m-d H:i:s")>=$waktusepuluhmenitkedepan)&&($row->STATUS_KURIR=='4')) {
                    // sudah lewat 10 menit
                    $update['STATUS_KURIR']='6';
                    $update['STATUS_PESANAN']='x';
                    $this->db->where('ID_PESANAN',$order_id);
                    $this->db->update('pesanan',$update);

                    $pesananheader=$this->mdl_api->getDetailPesananHeader($order_id);                    
                    $data['status']=$pesananheader->STATUS_KURIR;
                    //$data['waktu']="Lewat sepuluh menit";    
                    
                    $judul_notif="Kurir tidak ditemukan :(";
                    $body_notif="Para kurir saat ini sedang sibuk jadi gak bisa ngambil pesanan kamu. Mohon coba lagi beberapa saat yaaa.";                                
                } else {
                   // $data['waktu']="belum sepuluh menit";    
                }

                $response['data']=$data;

                
            }
        }
            
        //$this->response($response);

        $this->mdl_api->cetakjson($response);
        // jika gagal == 1
        if ($judul_notif!="") {
            // kirim notif ke user
            //  $this->mdl_api->sendnotif($judul_notif,$body_notif,$pesananheader->ID_USER);                                    
        }
        
    }

    function getAvailableOrder_post() {
    	$user_id=$this->post('id_user');
        $token=$this->post('token');
        if (!$this->mdl_api->cektokenkurir($user_id,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } else {
        	$status=$this->mdl_api->getSuccessResponseVariable("Success");
            $response=$status;

            $pesanan=$this->mdl_api->getpesanansearching();  
            if ($pesanan) {          
                foreach ($pesanan AS $p) {
                	$data=array();
                	$data['id_order']=$p->ID_PESANAN;
                	$data['date']=$this->mdl_api->konversitanggal('d M Y',$p->TANGGAL_PESANAN);
                	$data['qty']=$this->mdl_api->getjumlahitempesanan($p->ID_PESANAN);
                	$data['time']=$this->mdl_api->konversitanggal('H:i',$p->TANGGAL_PESANAN);
                	$data['price']=$this->mdl_api->gettotalpesanan($p->ID_PESANAN)+$this->mdl_api->gettotalpesananadditional($p->ID_PESANAN);
                	$data['ongkir']=$p->ONGKIR_PESANAN+$p->ONGKOS_BEDA_SUB_KATEGORI;
                	$response['data'][]=$data;
                }
            } else {
                $response['data']=NULL;
            }    
        }
        $this->response($response);
    }

    function getDetailOrder_post() {        
        $id_user=$this->post('id_user');
        $token=$this->post('token');
        $id_pesanan=$this->post('id_order');
        if (!$this->mdl_api->cektokenkurir($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } else {
            $status=$this->mdl_api->getSuccessResponseVariable("Success");
            $response=$status;            
            

            $produkpesanan=$this->mdl_api->getdetailprodukpesanan($id_pesanan);
            $produk=array();
            foreach ($produkpesanan AS $pp) {
                $temp['id']=$pp->ID_PRODUK;
                $temp['name']=$pp->NAMA_PRODUK;
                $temp['image']=$this->base_url_admin."".$pp->GAMBAR_PRODUK;
                $temp['price']=$pp->HARGA_PRODUK;
                $temp['qty']=$pp->QTY_PRODUK;
                $temp['max']=$pp->MAX_ORDER_PRODUK;
                $temp['subcategory']=$pp->NAMA_SUB_KATEGORI;
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
                $temp['subcategory']="";
                $produk[]=$temp;
            }

            $pesananheader=$this->mdl_api->getDetailPesananHeader($id_pesanan);
            $temproduk['address']=$pesananheader->LOKASI_PESANAN;
            $temproduk['ongkir']=$pesananheader->ONGKIR_PESANAN+$pesananheader->ONGKOS_BEDA_SUB_KATEGORI;
            $temproduk['catatan']=$pesananheader->CATATAN_PESANAN;
            $temproduk['produk']=$produk;
            $temproduk['price']=$this->mdl_api->gettotalpesanan($id_pesanan)+$this->mdl_api->gettotalpesananadditional($id_pesanan);
            $temproduk['status']=$pesananheader->STATUS_KURIR;
            $response['data']=$temproduk;

            

            // get detail user
            $detailuser=$this->mdl_api->getdetailuser($pesananheader->ID_USER);
            $user['name']=$detailuser->NAMA_USER;
            $user['lat']=$pesananheader->LAT_PESANAN;
            $user['lon']=$pesananheader->LONG_PESANAN;
            $user['phone']=$detailuser->NO_WHATSAPP_USER;            
            $response['data']['user']=$user;
        }
        $this->response($response);
    }

    function updateOrder_post() {
    	$id_user=$this->post('id_user');
        $token=$this->post('token');
        $id_pesanan=$this->post('id_order');
        $status_pesanan=$this->post('status');

        $judul_notif="";
        if (!$this->mdl_api->cektokenkurir($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } else {
            $status=$this->mdl_api->getSuccessResponseVariable("Success");
            $response=$status;  

            $pesanan=$this->mdl_api->getDetailPesananHeader($id_pesanan);
            if (($status_pesanan=='1')&&($this->mdl_api->cekstatuskurirsedangantarpesanan($id_user))) {
                // artinya kurir ini masih ada pesanan yang belum selesai (on progress)
                $response['status']='500';
                $response['message']='Anda sudah mengambil pesanan. Harap selesaikan terlebih dahulu sebelum mengambil pesanan selanjutnya.';
                $this->response($response);
                die();
            } elseif (($pesanan->STATUS_KURIR=='4')&&($pesanan->ID_KURIR=='0')) {
                // artinya belum ada yg ambil pesanan
                $update['ID_KURIR']=$id_user;    
            } elseif ((($status_pesanan=='1')&&($pesanan->ID_KURIR!=$id_user))||($pesanan->ID_KURIR!=$id_user)) {
                // artinya pesanan sudah onprogress dan diambil kurir lain
                /*$response['status']='500';
                $response['message']='Pesanan sudah diambil kurir lain';*/
                $data['order_number']=$id_pesanan;
                $data['status']=$pesanan->STATUS_KURIR;
                $data['driver_id']=$pesanan->ID_KURIR;
                $response['data']=$data;   
                $this->response($response);
                die();
            } elseif ($status_pesanan=='2') {
                // pesanan selesai
                $update['STATUS_PESANAN']='1';
            } elseif ($status_pesanan=='3') {
                // pesanan dibatalkan
                $update['STATUS_PESANAN']='x';
            } 

            $update['STATUS_KURIR']=$status_pesanan;
            $this->db->where('ID_PESANAN',$id_pesanan);
            $this->db->update("pesanan",$update);            

            $pesanan=$this->mdl_api->getDetailPesananHeader($id_pesanan);            
            $data['order_number']=$id_pesanan;
            $data['status']=$pesanan->STATUS_KURIR;
            $data['driver_id']=$pesanan->ID_KURIR;
            $response['data']=$data;        

            if ($status_pesanan=='1') {
                $judul_notif="Kurir ditemukan";
                $body_notif="Kurir sedang mengambil pesananmu. Mohon ditunggu ya.";
                
            } elseif ($status_pesanan=='5') {
                $judul_notif="Pesanan OTW";
                $body_notif="Pesanan sedang diantar menuju ke alamat kamu.";                
            } elseif ($status_pesanan=='2') {
                $judul_notif="Pesanan sudah sampai";
                $body_notif="Terima kasih sudah menggunakan layanan BakulEmak. Ditunggu pesanan berikutnya";                
            }    
        }

        //$this->response($response);
        $this->mdl_api->cetakjson($response);

        if ($judul_notif!="") {
            $this->mdl_api->sendnotif($judul_notif,$body_notif,$pesanan->ID_USER);
        }
    	
    }

    function changeDriverAccount_post() {
    	$id_user=$this->post('id_user');
        $token=$this->post('token');
        $old_pass=$this->post('old_pass');
        $new_pass=$this->post('new_pass');
        $no_whatsapp=$this->post('no_whatsapp');
        if (!$this->mdl_api->cektokenkurir($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } elseif (($old_pass=="")||($new_pass=="")||($no_whatsapp=="")) {
            $response['status']='500';
            $response['message']='Mohon lengkapi data';
        } else {
            $status=$this->mdl_api->getSuccessResponseVariable("Success");
            $response=$status;          	

            // cek password lama
        	$kurir=$this->mdl_api->cekakunkurir($id_user,$old_pass);
        	if (!$kurir) {
        		$response['status']='500';
           		$response['message']='Password lama salah';	
        	} else {
                if ($kurir->NO_WHATSAPP_KURIR==$no_whatsapp) {
                    // artinya hanya ganti password
                    $update['PASSWORD_KURIR']=md5($new_pass);                    
                    $this->db->update('kurir',$update);
                } else {
                    // cek existing nomor kurir
                    $eksisnomorhp=$this->mdl_api->cekeksistingnomorhpkurir($no_whatsapp);
                    if ($eksisnomorhp) {
                        $response['status']='500';
                        $response['message']='Nomor whatsapp sudah digunakan pengguna lain';
                        $this->response($response);
                        die();
                    } else {
                        $update['PASSWORD_KURIR']=md5($new_pass);                    
                        $update['NO_WHATSAPP_KURIR']=$no_whatsapp;
                        $this->db->update('kurir',$update);  
                    }
                    
                }
            		
            		
        	}

            /*
        	if (isset($this->post('image'))) {
        		$this->load->library('upload');
				$config['upload_path'] = './fotokurir/';
				$config['allowed_types'] = 'gif|jpg|png|jpeg';
				$random_string=$this->mdl_api->generateRandomString(5);			
				$config['file_name'] = "kurir_".$id_kurir.$random_string;
				$this->upload->initialize($config);

				if ($this->upload->do_upload()) {
					$upload_data = $this->upload->data();
					$c['image_library'] = 'gd2';
					$c['source_image'] = $upload_data['full_path'];
				    $c['maintain_ratio'] = FALSE;
				    $c['width']     = 150;
				    $c['height']   = 150;
				    $this->load->library('image_lib', $c); 
				    $this->image_lib->resize();
				    $nama_gambar="fotokurir/".$upload_data['file_name'];				    
				    $d['FOTO_KURIR']=$nama_gambar;

				    // update field gambar setelah upload
				    $this->db->where('ID_KURIR',$id_user);
				    $this->db->update('kurir',$d);
				}
        	}*/

            if ($response['status']!='500') {
            	$kurirnew=$this->mdl_api->getdetailkurir($id_user);
            	$data['id']=$kurirnew->ID_KURIR;
            	$data['name']=$kurirnew->NAMA_KURIR;
            	$data['whatsapp']=$kurirnew->NO_WHATSAPP_KURIR;
            	$data['token']=$kurirnew->TOKEN_KURIR;
            	$data['created_at']=$this->mdl_api->konversitanggal('d F Y',$kurirnew->DATE_ADD);
            	$data['image']=$this->base_url_admin.$kurirnew->FOTO_KURIR;
            	$response['data']=$data;
            }    
        }

        $this->response($response);
    }

    function getHistoryOrder_post() {
        $id_user=$this->post('id_user');
        $token=$this->post('token');
        if (!$this->mdl_api->cektokenkurir($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } else {
            $status=$this->mdl_api->getSuccessResponseVariable("Success");
            $response=$status; 

            // month yyyy-mm
            $month=$this->input->post('month');
            $history=$this->mdl_api->gethistorypesanandriver($id_user,$month);
            $data=array();
            foreach ($history AS $h) {
                $data['id_order']=$h->ID_PESANAN;
                $data['date']=$this->mdl_api->konversitanggal('d M Y',$h->TANGGAL_PESANAN);
                $data['qty']=$this->mdl_api->getjumlahitempesanan($h->ID_PESANAN);
                $data['time']=$this->mdl_api->konversitanggal('H:i',$h->TANGGAL_PESANAN);
                $data['price']=$this->mdl_api->gettotalpesanan($h->ID_PESANAN)+$this->mdl_api->gettotalpesananadditional($h->ID_PESANAN);
                $data['ongkir']=$h->ONGKIR_PESANAN+$h->ONGKOS_BEDA_SUB_KATEGORI;
                $data['selisih']=$this->mdl_api->getselisihpesanan($h->ID_PESANAN);
                $data['status']=$h->STATUS_KURIR;
                $response['data'][]=$data;
            }
        }
        $this->response($response);
    }

    /*
    function driverUpdateOrder_post() {
        $id_user=$this->post('id_user');
        $token=$this->post('token');
        $id_pesanan=$this->post('order_id');
        $inputan=$this->post('data');
        $hasil=json_decode($inputan);
        $pesananheader=$this->mdl_api->getDetailPesananHeader($id_pesanan);  
        if (!$this->mdl_api->cektokenkurir($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } elseif ($pesananheader->ID_KURIR!=$id_user) { 
            $response['status']='500';
            $response['message']='Pesanan error';
        } else {
            if (empty($hasil->produk)) {
                $response['status']='403';
                $response['message']='Produk belum dipilih';
            } else {
                $status=$this->mdl_api->getSuccessResponseVariable("Success");
                $response=$status;

                $product=$hasil->produk; 
                if (!empty($product)) {
                    // delete dulu
                    $this->db->where('ID_PESANAN',$id_pesanan);
                    $this->db->delete('pesanan_detail');

                    foreach ($product AS $p) {                
                        $pesanandetail['ID_PESANAN']=$id_pesanan;
                        $pesanandetail['ID_PRODUK']=$p->id;
                        $pesanandetail['HARGA_PRODUK']=$this->mdl_api->gethargaproduk($p->id);
                        $pesanandetail['QTY_PRODUK']=$p->qty;
                        $this->db->insert('pesanan_detail',$pesanandetail);
                    }
                } 
                
                $data['address']=$pesananheader->LOKASI_PESANAN;
                $data['catatan']=$pesananheader->CATATAN_PESANAN;
                $data['ongkir']=$pesananheader->ONGKIR_PESANAN+$pesananheader->ONGKOS_BEDA_SUB_KATEGORI;
                $data['status']=$pesananheader->STATUS_KURIR;

                    // get list product
                    $produkpesanan=$this->mdl_api->getdetailprodukpesanan($id_pesanan);
                    $produk=array();
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

                $data['produk']=$produk;
                $data['price']=$this->mdl_api->gettotalpesanan($id_pesanan)+$this->mdl_api->gettotalpesananadditional($id_pesanan);
                 // get detail user
                $detailuser=$this->mdl_api->getdetailuser($pesananheader->ID_USER);
                $user['name']=$detailuser->NAMA_USER;
                $user['lat']=$pesananheader->LAT_PESANAN;
                $user['lon']=$pesananheader->LONG_PESANAN;
                $user['phone']=$detailuser->NO_WHATSAPP_USER;   
                $data['user']=$user;

                $response['data']=$data;

            } // end if not empty product
        }

        $this->response($response);
    }*/


    function driverUpdateOrder_post() {
    	$inputan=$this->post('data');
        $hasil=json_decode($inputan);
        $id_user=$hasil->id_user;
        $id_pesanan=$hasil->id_order;
        $token=$hasil->token;

        $pesananheader=$this->mdl_api->getDetailPesananHeader($id_pesanan);  
        if (!$this->mdl_api->cektokenkurir($id_user,$token)) {
            $response['status']='401';
            $response['message']='Anda sudah login di perangkat lain';
        } elseif ($pesananheader->ID_KURIR!=$id_user) { 
            $response['status']='500';
            $response['message']='Pesanan error';
        } else {
            if (empty($hasil->product)) {
                $response['status']='403';
                $response['message']='Produk belum dipilih';
            } else {
                $status=$this->mdl_api->getSuccessResponseVariable("Success");
                $response=$status;

                $product=$hasil->product; 
                if (!empty($product)) {
                    // delete dulu
                    $this->db->where('ID_PESANAN',$id_pesanan);
                    $this->db->delete('pesanan_detail');

                    foreach ($product AS $p) {                
                        $pesanandetail['ID_PESANAN']=$id_pesanan;
                        $pesanandetail['ID_PRODUK']=$p->id;
                        $pesanandetail['HARGA_PRODUK']=$this->mdl_api->gethargaproduk($p->id);
                        $pesanandetail['SELISIH_PRODUK']=$this->mdl_api->getselisihproduk($p->id);
                        $pesanandetail['QTY_PRODUK']=$p->qty;
                        $this->db->insert('pesanan_detail',$pesanandetail);
                    }

                    // update ongkir dan beda subkategori
                    // cek beda subkategori
                    $jumlahsubkategori=$this->mdl_api-> getjumlahbedasubkategoripesanan($id_pesanan);
                    if ($jumlahsubkategori>1) {
                        $hargabedasubkategori=($jumlahsubkategori-1)*($this->mdl_api->getHargaBedaSubKategori());
                        $this->mdl_api->updatehargabedasubkategori($id_pesanan,$hargabedasubkategori);
                    }
                } 
                
                $data['address']=$pesananheader->LOKASI_PESANAN;
                $data['catatan']=$pesananheader->CATATAN_PESANAN;
                $data['ongkir']=$pesananheader->ONGKIR_PESANAN+$pesananheader->ONGKOS_BEDA_SUB_KATEGORI;
                $data['status']=$pesananheader->STATUS_KURIR;

                    // get list product
                    $produkpesanan=$this->mdl_api->getdetailprodukpesanan($id_pesanan);
                    $produk=array();
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

                $data['produk']=$produk;
                $data['price']=$this->mdl_api->gettotalpesanan($id_pesanan)+$this->mdl_api->gettotalpesananadditional($id_pesanan);
                 // get detail user
                $detailuser=$this->mdl_api->getdetailuser($pesananheader->ID_USER);
                $user['name']=$detailuser->NAMA_USER;
                $user['lat']=$pesananheader->LAT_PESANAN;
                $user['lon']=$pesananheader->LONG_PESANAN;
                $user['phone']=$detailuser->NO_WHATSAPP_USER;   
                $data['user']=$user;

                $response['data']=$data;

            } // end if not empty product
        }

        $this->response($response);
    }

    function validateDriver_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');

        if (!$this->mdl_api->cektokenkurir($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data['status']='200';
            $data['messages']="Success";
            $data['success']=true;
            $user=$this->mdl_api->getdetailkurir($id_user);
            $temp=array(
                'id' => $user->ID_KURIR,
                'name' => $user->NAMA_KURIR,
                'whatsapp' => $user->NO_WHATSAPP_KURIR,
                'token' => $user->TOKEN_KURIR,
                'created_at' => $this->konversitanggal('d F Y',$user->DATE_ADD),
                'image' => $this->base_url_admin.$user->FOTO_KURIR
            );            
            $data['data']=$temp;
        }
        $this->response($data);
    }

    function trackingDriver_post() {
        $token=$this->post('token');
        $id_user=$this->post('id_user');

        if (!$this->mdl_api->cektokenkurir($id_user,$token)) {
            $data['status']='401';
            $data['message']='Anda sudah login di perangkat lain';
        } else {
            $data['status']='200';
            $data['messages']="Success";
            $data['success']=true;

            $update['LAT_KURIR_SEKARANG']=$this->post('lat');
            $update['LONG_KURIR_SEKARANG']=$this->post('lon');
            $this->db->where('ID_KURIR',$id_user);
            $this->db->update('kurir',$update);
        }
        $this->response($data);
    }

    /* MERCHANT WITH X-API-KEY */

    function getTokenHeader()
    {
        $header = getallheaders();
        return $header['X-API-KEY'];
    }

    function registermerchant_post()
    {
        $data = [
            "NAMA_TOKO"     => $this->post('NAMA_TOKO'),
            "USER_TOKO"     => $this->post('USER_TOKO'),
            "PASS_TOKO"     => MD5($this->post('PASS_TOKO')),
            "TOKEN_TOKO"    => random_string('alnum', 8),
            "DATE"          => date('yy-m-d h:i:s'),
            "ADD_BY"        => "system"
        ];

        $register_merchant = $this->mdl_merchant->register_merchant($data);

        if ($register_merchant["status"])
        {

            // kirim token ke wa (check no wa -> kirim token ke wa)
            $pesan = "Token Akun Baru Mitra Bakul Emak : ".$data["TOKEN_TOKO"];
            $this->mdl_api->sendWhatsapp($data["USER_TOKO"], $pesan);

            // return token 
            $this->set_response([
                'status' => TRUE,
                'data'   => $data['TOKEN_TOKO']." ".$whatsapp,
                'message'=> 'Success'
                 ], REST_Controller::HTTP_OK);
            
        }
        else
        {
            $this->set_response([
                'status' => FALSE,
                'message'=> 'Not Acceptable ' . $register_merchant["error"]["message"]
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
    }

    function loginmerchant_post()
    {
        $user = $this->post('user_login');
        $pass = $this->post('pass_login');

        $merchant = $this->mdl_merchant->login_merchant($user, $pass);

        if (!$merchant["status"])
        {
            $this->set_response([
                   'status' => FALSE,
                   'message' => $merchant["error"]
                    ], REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $this->set_response([
                   'status' => TRUE,
                   'data'   => $merchant["data"],
                   'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
    }

    function verify_get()
    {
        $token  = $this->getTokenHeader();
        $user   = $this->mdl_merchant->verify($token);

        if ($user['status'])
        {
            $this->set_response([
                   'status' => TRUE,
                   'data'   => $user['data'],
                   'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                   'status' => FALSE,
                   'message' => $user['error']
                    ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    function activated_merchant_post()
    {
        $category   = $this->post('category_id');
        $token      = $this->post('token');

        $activated  = $this->mdl_merchant->activated_user($category, $token);

        if ($activated['status'])
        {
            $this->set_response([
                   'status' => TRUE,
                   'data'   => $token,
                   'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                'status' => FALSE,
                'message'=> 'Not Acceptable '.$activated["error"]
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
    }

    function close_and_open_post()
    {
        $token  = $this->getTokenHeader();
        $toggle = $this->mdl_merchant->close_and_open($token);

        if ($toggle["status"])
        {
            $this->set_response([
                   'status' => TRUE,
                   'message'=> 'Toko Buka'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                   'status' => FALSE,
                   'message'=> 'Toko Tutup'
                    ], REST_Controller::HTTP_OK);
        }
    }

    function time_merchant_post()
    {
        $token  = $this->getTokenHeader();

        $time = [
            "BUKA_SENIN"    => $this->post('BUKA_SENIN'),
            "TUTUP_SENIN"   => $this->post('TUTUP_SENIN'),
            "BUKA_SELASA"   => $this->post('BUKA_SELASA'),
            "TUTUP_RABU"    => $this->post('TUTUP_RABU'),
            "BUKA_KAMIS"    => $this->post('BUKA_KAMIS'),
            "TUTUP_KAMIS"   => $this->post('TUTUP_KAMIS'),
            "BUKA_JUMAT"    => $this->post('BUKA_JUMAT'),
            "TUTUP_JUMAT"   => $this->post('TUTUP_JUMAT'),
            "BUKA_SABTU"    => $this->post('BUKA_SABTU'),
            "TUTUP_SABTU"   => $this->post('TUTUP_SABTU'),
            "BUKA_MINGGU"   => $this->post('BUKA_MINGGU'),
            "TUTUP_MINGGU"  => $this->post('TUTUP_MINGGU'),
        ];

        $time_update = $this->mdl_merchant->time_merchant($token, $time);

        if ($time_update['status'])
        {
            $this->set_response([
                'status' => TRUE, 
                'message'=> 'Success'
            ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                'status'    => FALSE,
                'data'      => $time_update['error'],
                'message'   => 'Not Acceptable'
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
    }

    function update_merchant_post()
    {
        $token  = $this->getTokenHeader();

        $data = [
            "NAMA_TOKO"         => $this->post('NAMA_TOKO'),
            "NOHP_TOKO"         => $this->post('NOHP_TOKO'),
            "ALAMAT_TOKO"       => $this->post('ALAMAT_TOKO'),
            "KETERANGAN_TOKO"   => $this->post('KETERANGAN_TOKO'),
            "LAT_TOKO"          => $this->post('LAT_TOKO'),
            "LONG_TOKO"         => $this->post('LONG_TOKO'),
            "DATE_MODIFY"       => date('yy-m-d h:i:s'),
            "MODIFY_BY"         => $this->post('NAMA_TOKO')
        ];

        $update_merchant = $this->mdl_merchant->update_merchant($token, $data);

        if ($update_merchant["status"])
        {
            $this->set_response([
                'status' => TRUE,
                'message'=> 'Success'
            ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                'status' => FALSE,
                'message'=> 'Not Acceptable, ' . $update_merchant["error"]
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

    }

    function image_merchant_post()
    {
        $token  = $this->getTokenHeader();

        $config['upload_path']      = 'image_merchants/';
        $config['allowed_types']    = 'gif|jpg|png';
        $config['max_size']         = 2000;
        $config['max_width']        = 1500;
        $config['max_height']       = 1500;
        $config['overwrite']        = TRUE;
        $config['file_name']        = date('yymdhis').$token;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('IMAGE_STORE')) {
            $error = array('error' => $this->upload->display_errors());

            $this->set_response([
                'status' => FALSE,
                'message'=> 'Not Acceptable, ' . implode(" ", $error)
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);
        } else {

            $data = array('IMAGE_STORE' => $this->upload->data());

            $image_merchant = $this->mdl_merchant->image_merchant($token, $config['upload_path'].$config['file_name'].$this->upload->data('file_ext'));

            if ($image_merchant["status"])
            {
                $this->set_response([
                    'status' => TRUE,
                    'message'=> 'Success'
                ], REST_Controller::HTTP_OK);
            }
            else
            {
                $this->set_response([
                    'status' => FALSE,
                    'data'   => $image_merchant["error"], 
                    'message'=> 'Success'
                ], REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }
    }

    function change_password_post()
    {
        $token  = $this->getTokenHeader();

        $old_password       = $this->post('old_password');
        $new_password       = $this->post('new_password');
        $confirm_password   = $this->post('confirm_password');

        if ($new_password != $confirm_password)
        {
            $this->set_response([
                   'status' => FALSE,
                   'message' => 'Password Not Match'
                    ], REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $update_password = $this->mdl_merchant->updatePassword($token, ["old_password"=>$old_password, "new_password"=>$new_password]);

            if ($update_password["status"])
            {
                $this->set_response([
                       'status' => TRUE,
                       'message'=> 'password has been updated'
                    ], REST_Controller::HTTP_OK);
            }
            else
            {
                $this->set_response([
                    'status' => FALSE,
                    'message'=> 'Not Acceptable '.$update_password["message"]
                    ], REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }
    }

    /* PRODUCT WITH X-API-KEY */

    function product_list_get()
    {
        $token  = $this->getTokenHeader();
        $produk = $this->mdl_product->getProduk($token);

        if ($produk["status"])
        {
            $this->set_response([
                   'status' => TRUE,
                   'data'   => $produk["data"] ,
                   'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                   'status' => FALSE,
                   'message' => $produk["error"]
                    ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    function product_create_post()
    {
        $token  = $this->getTokenHeader();

        $config['upload_path']      = 'uploadproduk/';
        $config['allowed_types']    = 'gif|jpg|png';
        $config['max_size']         = 2000;
        $config['max_width']        = 1500;
        $config['max_height']       = 1500;
        $config['overwrite']        = TRUE;
        $config['file_name']        = date('yymdhis').$token;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('GAMBAR_PRODUK')) 
        {

            $error = array('error' => $this->upload->display_errors());

            $this->set_response([
                'status' => FALSE,
                'message'=> 'Not Acceptable, ' . implode(" ", $error)
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);

        } 
        else 
        {
            $file = array('GAMBAR_PRODUK' => $this->upload->data());
        }

        $data = [
            "NAMA_PRODUK"       => $this->post("NAMA_PRODUK"),
            "HARGA_PRODUK"      => $this->post("HARGA_PRODUK"),
            "GAMBAR_PRODUK"     => $file == null ? null : $config['upload_path'].$config['file_name'].$this->upload->data('file_ext'),
            "STOK_PRODUK"       => $this->post("STOK_PRODUK"),
            "MAX_ORDER_PRODUK"  => $this->post("MAX_ORDER_PRODUK")
        ];

        $produk = $this->mdl_product->createProduct($token, $data);

        if ($produk["status"])
        {
            $this->set_response([
                    'status' => TRUE,
                    'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                    'status' => FALSE,
                    'message' => $produk["error"]
                    ], REST_Controller::HTTP_NOT_FOUND);
        }
    
    }

    function product_update_post($id)
    {
        $token  = $this->getTokenHeader();

        $data = [
            "NAMA_PRODUK"       => $this->post("NAMA_PRODUK"),
            "HARGA_PRODUK"      => $this->post("HARGA_PRODUK"),
            "STOK_PRODUK"       => $this->post("STOK_PRODUK"),
            "MAX_ORDER_PRODUK"  => $this->post("MAX_ORDER_PRODUK")
        ];

        $config['upload_path']      = 'uploadproduk/';
        $config['allowed_types']    = 'gif|jpg|png';
        $config['max_size']         = 2000;
        $config['max_width']        = 1500;
        $config['max_height']       = 1500;
        $config['overwrite']        = TRUE;
        $config['file_name']        = date('yymdhis').$token;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('GAMBAR_PRODUK')) 
        {

            $error = array('error' => $this->upload->display_errors());

            $this->set_response([
                'status' => FALSE,
                'message'=> 'Not Acceptable, ' . implode(" ", $error)
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);

        } 
        else 
        {
            $file = array('GAMBAR_PRODUK' => $this->upload->data());
            $data["GAMBAR_PRODUK"] = $config['upload_path'].$config['file_name'].$this->upload->data('file_ext');
        }

        $produk = $this->mdl_product->updateProduct($token, $data, $id);

        // $this->response($produk);

        if ($produk["status"])
        {
            $this->set_response([
                   'status' => TRUE,
                   'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                   'status' => FALSE,
                   'message' => $produk["error"]
                    ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    function update_stock_post($id)
    {
        $id_produk  = $id;
        $token      = $this->getTokenHeader();

        $data = [
            "STOK_PRODUK" => $this->post("STOK_PRODUK") ?? 0
        ];

        $produk_update = $this->mdl_product->stockProduct($token, $data, $id);

        if ($produk_update["status"])
        {
            $this->set_response([
                   'status' => TRUE,
                   'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                   'status' => FALSE,
                   'message' => $produk_update["error"]
                    ], REST_Controller::HTTP_NOT_FOUND);
        }

    }

    function update_price_post($id)
    {
        $id_produk  = $id;
        $token      = $this->getTokenHeader();

        $data = [
            "HARGA_PRODUK" => $this->post("HARGA_PRODUK") ?? 0
        ];

        $produk_update = $this->mdl_product->priceProduct($token, $data, $id);

        if ($produk_update["status"])
        {
            $this->set_response([
                   'status' => TRUE,
                   'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                   'status' => FALSE,
                   'message' => $produk_update["error"]
                    ], REST_Controller::HTTP_NOT_FOUND);
        }

    }

    function update_show_post($id)
    {
        $id_produk  = $id;
        $token      = $this->getTokenHeader();

        $produk_update = $this->mdl_product->showProduct($token, $id);

        if ($produk_update["status"])
        {
            $this->set_response([
                   'status' => TRUE,
                   'message'=> 'Success'
                    ], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->set_response([
                   'status' => FALSE,
                   'message' => $produk_update["error"]
                    ], REST_Controller::HTTP_NOT_FOUND);
        }

    }

    function test_get()
    {
        // 9668
        // $total_selisih = $this->mdl_api->getselisihpesanan("9664");
        // $this->response($total_selisih);

        $toko = $this->mdl_price_rates->getDistanceDifferentMerchant("9685");
        $this->response($toko);
    }

    function test_price_rate_post()
    {       
        $inputan    = $this->post("data");
        $hasil      = json_decode($inputan);

        $id_user    = $hasil->id_user;
        $token      = $hasil->token;
        if (!$this->mdl_api->cektokenuser($id_user,$token)) 
        {
            $response['status'] ='401';
            $response['message']='Anda sudah login di perangkat lain';
        } 
        else 
        {
            if (empty($hasil->product)) 
            {
                $response['status'] ='403';
                $response['message']='Produk belum dipilih';
            } 
            else 
            {
                $status     = $this->mdl_api->getSuccessResponseVariable("Success");
                $response   = $status;

                $insert['TANGGAL_PESANAN']  = date("Y-m-d H:i:s");
                $insert['ID_USER']          = $id_user;
                $insert['CATATAN_PESANAN']  = $hasil->catatan;

                $location_user              = $hasil->location;            
                $insert['LAT_PESANAN']      = $location_user->lat;
                $insert['LONG_PESANAN']     = $location_user->lon;                

                // $tokoterdekat               = $this->mdl_api->gettokoterdekat($location_user->lat,$location_user->lon);
                // $insert['LAT_MERCHANT']     = $tokoterdekat->LAT_TOKO;
                // $insert['LONG_MERCHANT']    = $tokoterdekat->LONG_TOKO;
                // $insert['ONGKIR_PESANAN']   = $this->mdl_api->hitungbiayaantar($tokoterdekat->distance);

                $nearestMerchant            = $this->mdl_price_rates->getDistanceAllMerchant($location_user->lat,$location_user->lon);
                $insert['LAT_MERCHANT']     = $nearestMerchant->LAT_TOKO;
                $insert['LONG_MERCHANT']    = $nearestMerchant->LONG_TOKO;
                $insert['ONGKIR_PESANAN']   = $this->mdl_price_rates->calculatePriceRates($nearestMerchant->distance, 1);

                $insert['STATUS_KURIR']     = "4";

                // cek apakah pesanan duplikat 
                $product = $hasil->product;
                if ($this->mdl_api->cekpesananduplikat($id_user,$insert,$product)) 
                {
                	$response['status']     = '500';
                	$response['message']    = 'Pesanan anda sedang diproses. Mohon tunggu beberapa saat untuk memesan kembali.';
                	$this->response($response);
                	die();
                }

                $this->db->insert('pesanan',$insert);

                $id_pesanan = $this->db->insert_id();

                $temproduk['order_number']  = $id_pesanan;
                $temproduk['status']        = $insert['STATUS_KURIR']; 

                foreach ($product AS $p) 
                {                
                    $pesanandetail['ID_PESANAN']    = $id_pesanan;
                    $pesanandetail['ID_PRODUK']     = $p->id;
                    $pesanandetail['HARGA_PRODUK']  = $this->mdl_api->gethargaproduk($p->id);
                    $pesanandetail['SELISIH_PRODUK']= $this->mdl_api->getselisihproduk($p->id);
                    $pesanandetail['QTY_PRODUK']    = $p->qty;

                    $this->db->insert('pesanan_detail',$pesanandetail);
                }

                // cek beda subkategori
                $jumlahsubkategori      = $this->mdl_price_rates->getDistanceDifferentMerchant($id_pesanan);
                $hargabedasubkategori   = $this->mdl_price_rates->calculatePriceRates($jumlahsubkategori, 2);
                $this->mdl_api->updatehargabedasubkategori($id_pesanan, $hargabedasubkategori);

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

                $temproduk['price']=$this->mdl_api->gettotalbayarpesanan($id_pesanan);
                $temproduk['produk']=$produk;
                $response['data']=$temproduk;         

                // update lokasi async   
                $this->mdl_api->updatelokasipesanan($this->base_url_admin.'home/updatenamalokasipesanan/', $id_pesanan);   
                
            }    
        }

        $this->response($response);
        // $this->mdl_api->cetakjson($response);
        
        // $judul_notif="Pesanan baru telah diterima";
        // $body_notif="Silahkan cek aplikasi kurir BakulEmak";
        // $this->mdl_api->sendnotif($judul_notif,$body_notif,"Driver");

    }

}
?>
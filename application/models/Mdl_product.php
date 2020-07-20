<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 1. Ganti Mdl_perfectmodels dengan nama model.
// 2. Ganti 'tablename' dengan nama tabel.


class Mdl_product extends CI_Model 
{

    private $table_product = 'produk';

    function __construct() {
        parent::__construct();
    }

    function getProduk($token)
    {

        $check_account = $this->db->where('TOKEN_TOKO', $token)->get('toko')->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $sub_kategori = $this->db->where('ID_TOKO', $check_account->ID_TOKO)->get('subkategori')->row();

            if (count($sub_kategori) == 1)
            {
                $get_product = $this->db->where('ID_SUB_KATEGORI', $sub_kategori->ID_SUB_KATEGORI)->get('produk')->result();
                
                return [
                    "status" => true,
                    "data"  => $get_product
                ];
            }
            else
            {
                return [
                    "status" => false,
                    "error" => "account not activated"
                ];
            }
        }
    }

    function createProduct($token, $data)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get('toko')->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $sub_kategori = $this->db->where('ID_TOKO', $check_account->ID_TOKO)->get('subkategori')->row();

            if (count($sub_kategori) == 1)
            {
                $count_product = $this->db->where('ID_SUB_KATEGORI', $sub_kategori->ID_SUB_KATEGORI)->get('produk')->num_rows();
                $kode_product  =  $token."-".str_pad(($count_product + 1), 3, "0", STR_PAD_LEFT);

                $data = [
                    "ID_KATEGORI"       => $sub_kategori->ID_KATEGORI,
                    "ID_SUB_KATEGORI"   => $sub_kategori->ID_SUB_KATEGORI,
                    "KODE_PRODUK"       => $kode_product,
                    "NAMA_PRODUK"       => $data["NAMA_PRODUK"],
                    "GAMBAR_PRODUK"     => $data["GAMBAR_PRODUK"] ?? null,
                    "HARGA_PRODUK"      => $data["HARGA_PRODUK"],
                    "STOK_PRODUK"       => $data["STOK_PRODUK"],
                    "MAX_ORDER_PRODUK"  => $data["MAX_ORDER_PRODUK"],
                    "STATUS_PRODUK"     => '1',
                    "SHOW_PRODUK"       => '1',
                    "DATE_ADD"          => date('yy-m-d h:i:s'),
                    "ADD_BY"            => $sub_kategori->NAMA_SUB_KATEGORI,
                    "SELISIH_PRODUK"    => 0
                ];

                $createProduct = $this->db->insert('produk', $data);

                if ($createProduct)
                {
                    return [
                        "status" => true,
                    ];
                }
                else
                {
                    return [
                        "status" => false,
                        "error"  => $this->db->error()["message"]
                    ];
                }
            }
            else
            {
                return [
                    "status" => false,
                    "error" => "account not activated"
                ];
            }
        }
    }

    function updateProduct($token, $data, $id)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get('toko')->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $sub_kategori = $this->db->where('ID_TOKO', $check_account->ID_TOKO)->get('subkategori')->row();

            if (count($sub_kategori) == 1)
            {
                $update = [
                    "NAMA_PRODUK"       => $data["NAMA_PRODUK"],
                    "GAMBAR_PRODUK"     => $data["GAMBAR_PRODUK"] ?? null,
                    "HARGA_PRODUK"      => $data["HARGA_PRODUK"],
                    "STOK_PRODUK"       => $data["STOK_PRODUK"],
                    "MAX_ORDER_PRODUK"  => $data["MAX_ORDER_PRODUK"],
                ];

                $update_stock = $this->db->where('ID_PRODUK', $id)->where('ID_SUB_KATEGORI', $sub_kategori->ID_SUB_KATEGORI)->update('produk', $update);
                return [
                    "status" => true,
                ];

                // if ($update_stock)
                // {
                //     return [
                //         "status" => true,
                //     ];
                // }
                // else
                // {
                //     return [
                //         "status" => false,
                //         "error"  => $this->db->error()["message"]
                //     ];
                // }
            }
            else
            {
                return [
                    "status" => false,
                    "error" => "account not activated"
                ];
            }
        }
    }

    function stockProduct($token, $data, $id)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get('toko')->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $sub_kategori = $this->db->where('ID_TOKO', $check_account->ID_TOKO)->get('subkategori')->row();

            if (count($sub_kategori) == 1)
            {
                $update_stock = $this->db->where('ID_PRODUK', $id)->where('ID_SUB_KATEGORI', $sub_kategori->ID_SUB_KATEGORI)->update('produk', $data);

                if ($update_stock)
                {
                    return [
                        "status" => true,
                    ];
                }
                else
                {
                    return [
                        "status" => false,
                        "error"  => $this->db->error()["message"]
                    ];
                }
            }
            else
            {
                return [
                    "status" => false,
                    "error" => "account not activated"
                ];
            }
        }
    }

    function priceProduct($token, $data, $id)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get('toko')->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $sub_kategori = $this->db->where('ID_TOKO', $check_account->ID_TOKO)->get('subkategori')->row();

            if (count($sub_kategori) == 1)
            {
                $update_stock = $this->db->where('ID_PRODUK', $id)->where('ID_SUB_KATEGORI', $sub_kategori->ID_SUB_KATEGORI)->update('produk', $data);

                if ($update_stock)
                {
                    return [
                        "status" => true,
                    ];
                }
                else
                {
                    return [
                        "status" => false,
                        "error"  => $this->db->error()["message"]
                    ];
                }
            }
            else
            {
                return [
                    "status" => false,
                    "error" => "account not activated"
                ];
            }
        }
    }

    function showProduct($token, $id)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get('toko')->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $sub_kategori = $this->db->where('ID_TOKO', $check_account->ID_TOKO)->get('subkategori')->row();

            if (count($sub_kategori) == 1)
            {
                
                if ($this->db->where('ID_PRODUK', $id)->where('ID_SUB_KATEGORI', $sub_kategori->ID_SUB_KATEGORI)->get('produk')->row()->SHOW_PRODUK == "1")
                {
                    $update_show = $this->db->where('ID_PRODUK', $id)->where('ID_SUB_KATEGORI', $sub_kategori->ID_SUB_KATEGORI)->update('produk', ["SHOW_PRODUK"=>"0"]);
                }
                else
                {
                    $update_show = $this->db->where('ID_PRODUK', $id)->where('ID_SUB_KATEGORI', $sub_kategori->ID_SUB_KATEGORI)->update('produk', ["SHOW_PRODUK"=>"1"]);
                }
                

                if ($update_show)
                {
                    return [
                        "status" => true,
                    ];
                }
                else
                {
                    return [
                        "status" => false,
                        "error"  => $this->db->error()["message"]
                    ];
                }
            }
            else
            {
                return [
                    "status" => false,
                    "error" => "account not activated"
                ];
            }
        }
    }

}
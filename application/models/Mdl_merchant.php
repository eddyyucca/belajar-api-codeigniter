<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 1. Ganti Mdl_perfectmodels dengan nama model.
// 2. Ganti 'tablename' dengan nama tabel.


class Mdl_merchant extends CI_Model 
{

    private $table_toko         = 'toko';
    private $table_sub_kategori = 'subkategori';

    function __construct() {
        parent::__construct();
    }

    function register_merchant($data)
    {
        $toko = [
            'NAMA_TOKO'			  => $data['NAMA_TOKO'],
            'NOHP_TOKO'			  => $data['USER_TOKO'],
            'USER_TOKO'			  => $data['USER_TOKO'],
            'PASS_TOKO'			  => $data['PASS_TOKO'],
            'TOKEN_TOKO'		  => $data['TOKEN_TOKO'],
            'DATE_ADD'			  => $data['DATE'],
            'ADD_BY'			  => $data['ADD_BY'],
            'DATE_MODIFY'		  => $data['DATE'],
            'MODIFY_BY'			  => $data['ADD_BY'],
        ];

        $insert_toko  = $this->db->insert($this->table_toko, $toko);

        if (!$insert_toko)
        {
            return $data = [
                'status' => false,
                'error'  => $this->db->error()
              ];
        }
        else
        {
            return $data = [
                'status' => true
              ];
        }
    }

    function login_merchant($user, $pass)
    {
        $user = $this->db->where('USER_TOKO', $user)->where('PASS_TOKO', MD5($pass))->get($this->table_toko)->result();

        if (count($user) == 1)
        {
            $subkategori = $this->db->where('ID_TOKO', $user[0]->ID_TOKO)->get($this->table_sub_kategori)->row();

            if (count($subkategori) == 1)
            {
                return [
                    "status" => true,
                    "data" => [
                        "toko" => $user,
                        "setting" => $subkategori
                    ]
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
        else
        {
            return [
                "status" => false,
                "error" => 'account not match'
            ];
        }
    }

    function verify($token)
    {
        $check_account      = $this->db->where('TOKEN_TOKO', $token)->get($this->table_toko)->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $check_subkategori  = $this->db->where('ID_TOKO', $check_account->ID_TOKO)->get($this->table_sub_kategori);

            if (count($check_subkategori->result()) == 1)
            {
                return [
                    "status" => true,
                    "data"  => [
                        "toko"      => $check_account,
                        "setting"   => $check_subkategori->row()
                    ]
                ];
            }
            else
            {
                return [
                    "status"    => false,
                    "error"     => "account do not activated"
                ];
            }
        }

    }

    function activated_user($category, $token)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get($this->table_toko)->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $subkategori = [
                'ID_TOKO '            => $check_account->ID_TOKO,
                'NAMA_SUB_KATEGORI'   => $check_account->NAMA_TOKO,
                'GAMBAR_SUB_KATEGORI' => '',
                'AKTIF'               => '1',
                'ID_KATEGORI'         => $category,
                'DATE_ADD'			  => date('yy-m-d h:i:s'),
                'ADD_BY'			  => $check_account->NAMA_TOKO,
            ];

            $insert_subkategori = $this->db->insert($this->table_sub_kategori, $subkategori);
            
            if (!$insert_subkategori)
            {
                return $data = [
                    'status' => false,
                    'error'  => $this->db->error()["message"]
                ];
            }
            else 
            {
                return $data = [
                    'status' => true
                ];
            }
        }
    }

    function close_and_open($token)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get($this->table_toko)->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $check_subkategori  = $this->db->where('ID_TOKO', $check_account->ID_TOKO)->where('NAMA_SUB_KATEGORI', $check_account->NAMA_TOKO)->get($this->table_sub_kategori)->row();
            if ($check_subkategori->AKTIF ==  "1")
            {
                $this->db->where('ID_TOKO', $check_account->ID_TOKO)->where('NAMA_SUB_KATEGORI', $check_account->NAMA_TOKO);
                $status = $this->db->update($this->table_sub_kategori, ["AKTIF" => "0", "DATE_MODIFY" => date('yy-m-d h:i:s'), 'MODIFY_BY' => $check_subkategori->NAMA_SUB_KATEGORI]);

                return [
                    "status" => false
                ];
            }
            else
            {
                $this->db->where('ID_TOKO', $check_account->ID_TOKO)->where('NAMA_SUB_KATEGORI', $check_account->NAMA_TOKO);
                $status = $this->db->update($this->table_sub_kategori, ["AKTIF" => "1", "DATE_MODIFY" => date('yy-m-d h:i:s'), 'MODIFY_BY' => $check_subkategori->NAMA_SUB_KATEGORI]);
                return [
                    "status" => true
                ];
            }
        }
    }

    function time_merchant($token, $time)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get($this->table_toko)->row();
        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $time["DATE_MODIFY"]    = date('yy-m-d h:i:s');
            $time["MODIFY_BY"]      = $check_account->NAMA_TOKO;

            $this->db->where('ID_TOKO', $check_account->ID_TOKO)->where('NAMA_SUB_KATEGORI', $check_account->NAMA_TOKO);
            $status = $this->db->update($this->table_sub_kategori, $time);

            if ($status)
            {
                return [
                    "status" => true
                ];
            }
            else
            {
                return [
                    "status"    => false,
                    "error"   => $this->db->error()["message"]
                ];
            }
            
        }
    }

    function update_merchant($token, $data)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get($this->table_toko)->row();

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $this->db->where('ID_TOKO', $check_account->ID_TOKO);
            $update_merchant = $this->db->update($this->table_toko, $data);

            if ($update_merchant)
            {
                $this->db->where('ID_TOKO', $check_account->ID_TOKO);
                $update_sub = $this->db->update($this->table_sub_kategori, [
                    "NAMA_SUB_KATEGORI"     => $data["NAMA_TOKO"],
                    "DATE_MODIFY"           => date('yy-m-d h:i:s'),
                    "MODIFY_BY"             => $data["NAMA_TOKO"]
                ]);

                if ($update_sub)
                {
                    return [
                        "status"    => true
                    ];
                }
                else
                {
                    return [
                        "status"    => false,
                        "error"     => "Update Sub is failed because " . $this->db->error()["message"]
                    ];
                }
            }
            else
            {
                return [
                    "status"    => false,
                    "error"     => "Update merchant is failed because " . $this->db->error()["message"]
                ];
            }
        }
    }

    function image_merchant($token, $filename)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get($this->table_toko)->row();
        $nama_toko     = $check_account->NAMA_TOKO;
        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $this->db->where('ID_TOKO', $check_account->ID_TOKO);
            $update_merchant = $this->db->update($this->table_toko, [
                "IMAGE_TOKO"     => $filename,
                "DATE_MODIFY"    => date('yy-m-d h:i:s'),
                "MODIFY_BY"      => $nama_toko
            ]);

            if ($update_merchant)
            {
                $this->db->where('ID_TOKO', $check_account->ID_TOKO);
                $update_sub = $this->db->update($this->table_sub_kategori, [
                    "GAMBAR_SUB_KATEGORI"   => $filename,
                    "DATE_MODIFY"           => date('yy-m-d h:i:s'),
                    "MODIFY_BY"             => $nama_toko
                ]);

                return [
                    "status" => true
                ];
            }
            else
            {
                return [
                    "status"    => false,
                    "error"     => "image merchant is failed because " . $this->db->error()["message"]
                ];
            }
        }
    }

    function updatePassword($token, $data)
    {
        $check_account = $this->db->where('TOKEN_TOKO', $token)->get($this->table_toko)->row();

        $nama_toko     = $check_account->NAMA_TOKO;

        if ($check_account == null)
        {
            return [
                "status"    => false,
                "error"     => "account not found"
            ];
        }
        else
        {
            $check_password = $this->db->where('TOKEN_TOKO', $token)->where('PASS_TOKO', MD5($data["old_password"]))->get($this->table_toko)->result();

            if (count($check_password) == 1)
            {
                $this->db->where('TOKEN_TOKO', $token);
                $update_password = $this->db->update($this->table_toko, [
                    "PASS_TOKO"             => MD5($data["new_password"]),
                    "DATE_MODIFY"           => date('yy-m-d h:i:s'),
                    "MODIFY_BY"             => $nama_toko
                ]);

                if ($update_password)
                {
                    return [
                        "status"    => true,
                    ];
                }
                else
                {
                    return [
                        "status"    => false,
                        "error"     => $this->db->error()["message"]
                    ];
                }
            }
            else
            {
                return [
                    "status"    => false,
                    "error"     => "old password is wrong"
                ];
            }
        }
    }

}
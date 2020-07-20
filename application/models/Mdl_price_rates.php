<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Mdl_price_rates extends CI_Model 
{
    function getDistanceAllMerchant($lat_user,$long_user) {
        $query	= "SELECT ID_TOKO, LAT_TOKO, LONG_TOKO, (6371 * 2 * ASIN(SQRT( POWER(SIN(( $lat_user - LAT_TOKO) *  pi()/180 / 2), 2) +COS( $lat_user * pi()/180) * COS(LAT_TOKO * pi()/180) * POWER(SIN(( $long_user - LONG_TOKO) * pi()/180 / 2), 2) ))) as distance  FROM toko  ORDER BY distance LIMIT 1";
        $query	= $this->db->query($query);
        return $query->row();
    }

    function calculatePriceRates($distance, $type)
    {
        $price = $this->db->where('distance <=', ROUND($distance))->order_by('distance', 'DESC')->get('price_rates')->row();
        // nearest merchant
        if ($type == 1)
        {
            return $price->price_rate;
        }
        else // second nearest merchant
        {
            return ROUND($distance) * $price->add_rate;
        }
    }

    function getDistanceDifferentMerchant($id_pesanan)
    {
        $pesanan = $this->db->where('ID_PESANAN', $id_pesanan)->get('pesanan')->row();

        // return 93 dan 87
        $this->db->distinct();
        $this->db->select('b.ID_SUB_KATEGORI');
        $this->db->where('a.ID_PESANAN',$id_pesanan);
        $this->db->join('produk b','a.ID_PRODUK=b.ID_PRODUK','INNER');
        $query = $this->db->get('pesanan_detail a')->result();

        $distance = 0;

        for ($i = 0; $i < count($query); $i++)
        {
            $this->db->select('toko.ID_TOKO, toko.NAMA_TOKO, toko.LAT_TOKO, toko.LONG_TOKO');
            $this->db->where('ID_SUB_KATEGORI', $query[$i]->ID_SUB_KATEGORI);
            $this->db->join('toko', 'subkategori.ID_TOKO = toko.ID_TOKO');
            $toko = $this->db->get('subkategori')->row();

            if (($toko != null) AND ($pesanan->LAT_MERCHANT != $toko->LAT_TOKO) AND ($pesanan->LONG_MERCHANT != $toko->LAT_TOKO))
            {
                $distance += $this->getDistanceAllMerchant($toko->LAT_TOKO,$toko->LONG_TOKO);
            }
        }

        return $distance;
        
    }
}
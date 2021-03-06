<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PilihanSekolah;
use DB;
use DateTime;

class SekolahController extends Controller
{
    function distance($lat1, $lon1, $lat2, $lon2) { 
        $pi80 = M_PI / 180; 
        $lat1 *= $pi80; 
        $lon1 *= $pi80; 
        $lat2 *= $pi80; 
        $lon2 *= $pi80; 
        $r = 6372.797; // mean radius of Earth in km 
        $dlat = $lat2 - $lat1; 
        $dlon = $lon2 - $lon1; 
        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2); 
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a)); 
        $km = $r * $c; 
        //echo ' '.$km; 
        return $km; 
    }

    public function index(Request $request)
    {
    	$limit = $request->limit ? $request->limit : 10;
        // $offset = $request->page ? ($request->page * $limit) : 0;
        $start = $request->start ? $request->start : 0;
        $searchText = $request->searchText ? $request->searchText : '';
        $status_sekolah = $request->status_sekolah ? $request->status_sekolah : '';
        $kode_wilayah = $request->kode_wilayah ? $request->kode_wilayah : null;
        $id_level_wilayah = $request->id_level_wilayah ? $request->id_level_wilayah : 0;
        $bentuk_pendidikan_id = $request->bentuk_pendidikan_id ? $request->bentuk_pendidikan_id : null;
        $lintang = $request->lintang ? $request->lintang : 0;
        $bujur = $request->bujur ? $request->bujur : 0;
        $nomor_pilihan = $request->nomor_pilihan ? $request->nomor_pilihan : null;
        $npsn = $request->npsn ? $request->npsn : null;
        $tampil_koreg = $request->tampil_koreg ? $request->tampil_koreg : null;
        $koreg = $request->koreg ? $request->koreg : null;

        $count = DB::connection('sqlsrv_2')->table('ppdb.sekolah AS sekolah')->where('sekolah.soft_delete', 0)
            ->join('ref.bentuk_pendidikan as bp','bp.bentuk_pendidikan_id','=','sekolah.bentuk_pendidikan_id')
            ->join('ref.mst_wilayah as kec','kec.kode_wilayah','=',DB::raw("LEFT(sekolah.kode_wilayah,6)"))
			->join('ref.mst_wilayah as kab','kec.mst_kode_wilayah','=','kab.kode_wilayah')
            ->leftJoin('ppdb.kuota_sekolah AS kouta', 'kouta.sekolah_id', '=', 'sekolah.sekolah_id')
			->join('ref.mst_wilayah as prop','kab.mst_kode_wilayah','=','prop.kode_wilayah');
        $sekolahs = DB::connection('sqlsrv_2')
            ->table('ppdb.sekolah as sekolah')
            ->join('ref.bentuk_pendidikan as bp','bp.bentuk_pendidikan_id','=','sekolah.bentuk_pendidikan_id')
            ->join('ref.mst_wilayah as kec','kec.kode_wilayah','=',DB::raw("LEFT(sekolah.kode_wilayah,6)"))
			->join('ref.mst_wilayah as kab','kec.mst_kode_wilayah','=','kab.kode_wilayah')
			->join('ref.mst_wilayah as prop','kab.mst_kode_wilayah','=','prop.kode_wilayah')
            ->leftJoin('ppdb.kuota_sekolah AS kouta', 'kouta.sekolah_id', '=', 'sekolah.sekolah_id')
        	->where('sekolah.soft_delete', 0)
        	// ->limit($limit)
            // ->offset($offset)
            ->skip($start)
            ->take($limit)
            ->select(
                'sekolah.*',
                'bp.nama as bentuk',
                'kouta.kuota AS kouta',
                DB::raw("(case when sekolah.status_sekolah = 1 then 'Negeri' else 'Swasta' end) as status")
                // DB::raw('null as kode_registrasi')
            )
        	->orderBy('sekolah.nama', 'ASC');

        if($searchText){
        	$count = $count->where('sekolah.npsn', 'ilike', '%'.$searchText.'%')->orWhere('sekolah.nama', 'ilike', '%'.$searchText.'%');
        	$sekolahs = $sekolahs->where('sekolah.npsn', 'ilike', '%'.$searchText.'%')->orWhere('sekolah.nama', 'ilike', '%'.$searchText.'%');
        }
        
        if($status_sekolah){
        	$count = $count->where('sekolah.status_sekolah', '=', $status_sekolah);
        	$sekolahs = $sekolahs->where('sekolah.status_sekolah', '=', $status_sekolah);
        }
        
        if($npsn){
        	$count = $count->where('sekolah.npsn', '=', $npsn);
        	$sekolahs = $sekolahs->where('sekolah.npsn', '=', $npsn);
        }
        
        if($bentuk_pendidikan_id){

            $arrBentukPendidikan = explode("-",$bentuk_pendidikan_id);

        	$count = $count->whereIn('sekolah.bentuk_pendidikan_id', $arrBentukPendidikan);
        	$sekolahs = $sekolahs->whereIn('sekolah.bentuk_pendidikan_id', $arrBentukPendidikan);
        }else{
            $count = $count->whereIn('sekolah.bentuk_pendidikan_id', array(5,6,9,10));
        	$sekolahs = $sekolahs->whereIn('sekolah.bentuk_pendidikan_id', array(5,6,9,10));
        }

        if($kode_wilayah){
			switch ($id_level_wilayah) {
				case 1:
					$count = $count->where('prop.kode_wilayah', "=", $kode_wilayah);
	    			$sekolahs = $sekolahs->where('prop.kode_wilayah', "=", $kode_wilayah);
					break;
				case 2:
					$count = $count->where('kab.kode_wilayah', "=", $kode_wilayah);
					$sekolahs = $sekolahs->where('kab.kode_wilayah', "=", $kode_wilayah);
					break;
				default:
					# code...
					break;
			}
		}
        if($request->sekolah_id){
            $sekolahs->limit(1)->where('sekolah.sekolah_id', $request->sekolah_id);
        }

        // return $sekolahs->toSql();die;

        $count = $count->count();
        $sekolahs = $sekolahs->get();

        $i = 0;
        foreach ($sekolahs as $key) {
            $pendaftar = PilihanSekolah::where('sekolah_id', $key->sekolah_id)->where('soft_delete', 0);

            if($nomor_pilihan){
                $pendaftar->where('urut_pilihan','=',$nomor_pilihan);
            }

            if($tampil_koreg){
                $sekolahs[$i]->koreg = (strtoupper(base_convert($sekolahs[$i]->kode_registrasi,10,32)) == $koreg ? '1' : '0');
                $sekolahs[$i]->koreg_dev = strtoupper(base_convert($sekolahs[$i]->kode_registrasi,10,32));
            }

            $pendaftar = $pendaftar->count();
            $kouta = $key->kouta;
            $terima = 0;

            $sekolahs[$i]->kouta = $kouta == null ? 0 : $kouta;
            $sekolahs[$i]->pendaftar = $pendaftar;
            $sekolahs[$i]->terima = $terima;
            $sekolahs[$i]->sisa_kouta = ($kouta - $terima);

            $jarak = self::distance($lintang,$bujur,$key->lintang,$key->bujur);
            $sekolahs[$i]->jarak = $jarak;

            $i++;
        }

        return response(
            [
                'rows' => $sekolahs,
                'count' => count($sekolahs),
                'countAll' => $count
            ],
            200
        );
    }

    public function getCalonPDSekolah(Request $request)
    {
        $limit = $request->limit ? $request->limit : 10;
        $start = $request->start ? $request->start : 0;
        $nomor_pilihan = $request->nomor_pilihan ? $request->nomor_pilihan : 1;
        $searchText = $request->searchText ? $request->searchText : '';
        $sekolah_id = $request->sekolah_id;

        // // $calon_pd = DB::connection('sqlsrv_2')
        // //     ->table('ppdb.pilihan_sekolah AS pilihan_sekolah')
        // //     ->select(
        // //         'pilihan_sekolah.*',
        // //         'sekolah.npsn',
        // //         'sekolah.nama AS nama_sekolah',
        // //         'calon_pd.nik',
        // //         'calon_pd.nama AS nama_calon_pd',
        // //         'calon_pd.jenis_kelamin AS jenis_kelamin',
        // //         'calon_pd.tempat_lahir AS tempat_lahir',
        // //         'calon_pd.tanggal_lahir AS tanggal_lahir',
        // //         'calon_pd.asal_sekolah_id AS asal_sekolah_id',
        // //         'calon_pd.alamat_tempat_tinggal AS alamat_tempat_tinggal',
        // //         'sekolah_asal.npsn AS npsn_sekolah_asal',
        // //         'sekolah_asal.nama AS nama_sekolah_asal',
        // //         'jalur.nama AS nama_jalur'
        // //     )
        // //     ->leftJoin('ppdb.sekolah AS sekolah', 'pilihan_sekolah.sekolah_id', '=', 'sekolah.sekolah_id')
        // //     ->leftJoin('ppdb.calon_peserta_didik AS calon_pd', 'pilihan_sekolah.calon_peserta_didik_id', '=', 'calon_pd.calon_peserta_didik_id')
        // //     ->leftJoin('ref.jalur AS jalur', 'pilihan_sekolah.jalur_id', '=', 'jalur.jalur_id')
        // //     ->leftJoin('ppdb.sekolah AS sekolah_asal', 'calon_pd.asal_sekolah_id', '=', 'sekolah_asal.sekolah_id')
        // //     ->where('pilihan_sekolah.soft_delete', 0)
        // //     ->where('pilihan_sekolah.sekolah_id', $sekolah_id)
        // //     ->orderBy('pilihan_sekolah.create_date', 'ASC')
        // //     ->limit($limit)
        // //     ->offset($offset);

        // // if($searchText){
        // //     $calon_pd = $calon_pd->where('calon_pd.nik', 'ilike', '%'.$searchText.'%')->orWhere('calon_pd.nama', 'ilike', '%'.$searchText.'%');
        // // }

        // // $calon_pd = $calon_pd->get();
        
        
        // $calon_pd = DB::connection('sqlsrv_2')->table('ppdb.pilihan_sekolah')
        // ->leftJoin('ppdb.konfirmasi_pendaftaran as konf','konf.calon_peserta_didik_id','=','pilihan_sekolah.calon_peserta_didik_id')
        // ->join('ppdb.calon_peserta_didik','calon_peserta_didik.calon_peserta_didik_id','=','pilihan_sekolah.calon_peserta_didik_id')
        // ->join('ref.jalur as jalur','jalur.jalur_id','=','pilihan_sekolah.jalur_id')
        // ->where('pilihan_sekolah.soft_delete','=',0)
        // ->where('calon_peserta_didik.soft_delete','=',0)
        // ->where('ppdb.pilihan_sekolah.sekolah_id','=',$sekolah_id)
        // ->where('urut_pilihan','=',$nomor_pilihan)
        // ->orderBy('pilihan_sekolah.sekolah_id')
        // ->orderBy('pilihan_sekolah.jalur_id')
        // ->select(
        //     DB::raw("ROW_NUMBER
        //     () OVER (
        //         PARTITION BY pilihan_sekolah.sekolah_id, pilihan_sekolah.jalur_id
        //     ORDER BY
        //         pilihan_sekolah.urut_pilihan ASC,
        //         COALESCE ( konf.status, 0 ) DESC,
        //         konf.last_update ASC,	
        //         pilihan_sekolah.create_date ASC
        //     ) AS urutan"),
        //     'urut_pilihan',
        //     DB::raw("COALESCE ( konf.status, 0 ) AS konfirmasi"),
        //     'konf.last_update',
        //     'pilihan_sekolah.create_date',
        //     'pilihan_sekolah.jalur_id',
        //     'calon_peserta_didik.nama',
        //     'pilihan_sekolah.sekolah_id',
        //     'pilihan_sekolah.calon_peserta_didik_id',
        //     'ppdb.calon_peserta_didik.tanggal_lahir',
        //     'ppdb.calon_peserta_didik.nama as nama_calon_pd',
        //     'ppdb.calon_peserta_didik.nik',
        //     'ppdb.calon_peserta_didik.tempat_lahir',
        //     'ppdb.calon_peserta_didik.jenis_kelamin',
        //     'jalur.nama as jalur',
        //     'ppdb.calon_peserta_didik.lintang',
        //     'ppdb.calon_peserta_didik.bujur'
        // );


        // // $calon_pd = DB::connection('sqlsrv_2')->select(DB::raw("SELECT 
        // //     ROW_NUMBER
        // //     () OVER (
        // //         PARTITION BY pilihan_sekolah.sekolah_id, pilihan_sekolah.jalur_id
        // //     ORDER BY
        // //         COALESCE ( konf.status, 0 ) DESC,
        // //         pilihan_sekolah.urut_pilihan ASC,
        // //         konf.last_update ASC,	
        // //         pilihan_sekolah.create_date ASC
        // //     ) AS urutan,
        // //     urut_pilihan,
        // //     COALESCE ( konf.status, 0 ) AS konfirmasi,
        // //     konf.last_update,
        // //     pilihan_sekolah.create_date,
        // //     pilihan_sekolah.jalur_id,
        // //     calon_peserta_didik.nama,
        // //     pilihan_sekolah.sekolah_id,
        // //     pilihan_sekolah.calon_peserta_didik_id
        // // FROM
        // //     ppdb.pilihan_sekolah
        // //     LEFT JOIN ppdb.konfirmasi_pendaftaran konf ON konf.calon_peserta_didik_id = pilihan_sekolah.calon_peserta_didik_id
        // //     JOIN ppdb.calon_peserta_didik ON calon_peserta_didik.calon_peserta_didik_id = pilihan_sekolah.calon_peserta_didik_id 
        // // WHERE
        // //     pilihan_sekolah.soft_delete = 0 
        // //     AND calon_peserta_didik.soft_delete = 0 
        // //     AND ppdb.pilihan_sekolah.sekolah_id = '".$sekolah_id."'
        // // ORDER BY
        // //     pilihan_sekolah.sekolah_id,
        // //     pilihan_sekolah.jalur_id"));

        // // if($searchText){
        // //     $calon_pd->where('ppdb.calon_peserta_didik.nama','ilike','%'.$searchText.'%');
        // // }

        // return $calon_pd->toSql();die;  

        if($searchText){
            $param_keyword = " AND (calon.nama ILIKE'%".$searchText."%' OR calon.nik ILIKE'%".$searchText."%' OR calon.nisn ILIKE'%".$searchText."%')";
        }else{
            $param_keyword = "";
        }

        $calon_pd_count = DB::connection('sqlsrv_2')->select(DB::raw("SELECT
                        sum(1) as total 
                    FROM
                        ppdb.calon_peserta_didik calon
                        JOIN (
                        SELECT ROW_NUMBER
                            () OVER (
                                PARTITION BY pilihan_sekolah.sekolah_id,
                                pilihan_sekolah.jalur_id 
                            ORDER BY
                                pilihan_sekolah.urut_pilihan ASC,
                                COALESCE ( konf.status, 0 ) DESC,
                                konf.last_update ASC,
                                pilihan_sekolah.create_date ASC 
                            ) AS urutan,
                            urut_pilihan,
                            COALESCE ( konf.status, 0 ) AS konfirmasi,
                            konf.last_update,
                            pilihan_sekolah.create_date,
                            pilihan_sekolah.jalur_id,
                            calon_peserta_didik.nama,
                            pilihan_sekolah.sekolah_id,
                            pilihan_sekolah.calon_peserta_didik_id,
                            ppdb.calon_peserta_didik.tanggal_lahir,
                            ppdb.calon_peserta_didik.nama AS nama_calon_pd,
                            ppdb.calon_peserta_didik.nik,
                            ppdb.calon_peserta_didik.tempat_lahir,
                            ppdb.calon_peserta_didik.jenis_kelamin,
                            jalur.nama AS jalur,
                            ppdb.calon_peserta_didik.lintang,
                            ppdb.calon_peserta_didik.bujur 
                        FROM
                            ppdb.pilihan_sekolah
                            LEFT JOIN ppdb.konfirmasi_pendaftaran AS konf ON konf.calon_peserta_didik_id = pilihan_sekolah.calon_peserta_didik_id
                            INNER JOIN ppdb.calon_peserta_didik ON calon_peserta_didik.calon_peserta_didik_id = pilihan_sekolah.calon_peserta_didik_id
                            INNER JOIN ref.jalur AS jalur ON jalur.jalur_id = pilihan_sekolah.jalur_id 
                        WHERE
                            pilihan_sekolah.soft_delete = 0 
                            AND calon_peserta_didik.soft_delete = 0 
                            AND ppdb.pilihan_sekolah.sekolah_id = '".$sekolah_id."' 
                            AND urut_pilihan = 1 
                        ORDER BY
                            pilihan_sekolah.sekolah_id ASC,
                            pilihan_sekolah.jalur_id ASC 
                        ) calon_urutan ON calon_urutan.calon_peserta_didik_id = calon.calon_peserta_didik_id 
                    WHERE
                        calon.soft_delete = 0 
                        ".$param_keyword));

        $calon_pd = DB::connection('sqlsrv_2')->select(DB::raw("SELECT
                        calon_urutan.* 
                    FROM
                        ppdb.calon_peserta_didik calon
                        JOIN (
                        SELECT ROW_NUMBER
                            () OVER (
                                PARTITION BY pilihan_sekolah.sekolah_id,
                                pilihan_sekolah.jalur_id 
                            ORDER BY
                                pilihan_sekolah.urut_pilihan ASC,
                                COALESCE ( konf.status, 0 ) DESC,
                                konf.last_update ASC,
                                pilihan_sekolah.create_date ASC 
                            ) AS urutan,
                            urut_pilihan,
                            COALESCE ( konf.status, 0 ) AS konfirmasi,
                            konf.last_update,
                            pilihan_sekolah.create_date,
                            pilihan_sekolah.jalur_id,
                            calon_peserta_didik.nama,
                            pilihan_sekolah.sekolah_id,
                            pilihan_sekolah.calon_peserta_didik_id,
                            ppdb.calon_peserta_didik.tanggal_lahir,
                            ppdb.calon_peserta_didik.nama AS nama_calon_pd,
                            ppdb.calon_peserta_didik.nik,
                            ppdb.calon_peserta_didik.tempat_lahir,
                            ppdb.calon_peserta_didik.jenis_kelamin,
                            jalur.nama AS jalur,
                            ppdb.calon_peserta_didik.lintang,
                            ppdb.calon_peserta_didik.bujur 
                        FROM
                            ppdb.pilihan_sekolah
                            LEFT JOIN ppdb.konfirmasi_pendaftaran AS konf ON konf.calon_peserta_didik_id = pilihan_sekolah.calon_peserta_didik_id
                            INNER JOIN ppdb.calon_peserta_didik ON calon_peserta_didik.calon_peserta_didik_id = pilihan_sekolah.calon_peserta_didik_id
                            INNER JOIN ref.jalur AS jalur ON jalur.jalur_id = pilihan_sekolah.jalur_id 
                        WHERE
                            pilihan_sekolah.soft_delete = 0 
                            AND calon_peserta_didik.soft_delete = 0 
                            AND ppdb.pilihan_sekolah.sekolah_id = '".$sekolah_id."' 
                            AND urut_pilihan = 1 
                        ORDER BY
                            pilihan_sekolah.sekolah_id ASC,
                            pilihan_sekolah.jalur_id ASC 
                        ) calon_urutan ON calon_urutan.calon_peserta_didik_id = calon.calon_peserta_didik_id 
                    WHERE
                        calon.soft_delete = 0 
                        ".$param_keyword.
                    "ORDER BY 
                        calon_urutan.sekolah_id ASC, 
                        calon_urutan.jalur_id ASC, 
                        calon_urutan.urutan ASC
                    OFFSET ".$start." LIMIT ".$limit));
        
        if(sizeof($calon_pd_count) > 0){
            $calon_pd_total = $calon_pd_count[0]->total;
        }else{
            $calon_pd_total = 0;
        }

        // if(sizeof($calon_pd) > 0){
        //     $calon_pd = $calon_pd->skip($start)->take($limit)->get();
        // }else{
        //     $calon_pd = $calon_pd->skip($start)->take($limit)->get();
        // }


        $i = 0;
        foreach ($calon_pd as $key) {
            $calon_pd[$i]->umur = $this->hitung_umur($key->tanggal_lahir);

            // if($searchText){
            //     if(strpos($calon_pd[$i]->nama_calon_pd, $searchText) === false){
            //         //nggak ketemu
            //     }else{
            //         //ketemu
            //     }
            // }

            $i++;
        }

        return response(
            [
                'rows' => $calon_pd,
                'count' => count($calon_pd),
                'countAll' => $calon_pd_total
            ],
            200
        );
    }

    public function hitung_umur($tanggal_lahir){
        $birthDate = new DateTime($tanggal_lahir);
        $today = new DateTime("today");
        if ($birthDate > $today) { 
            exit("0 tahun 0 bulan 0 hari");
        }
        $y = $today->diff($birthDate)->y;
        $m = $today->diff($birthDate)->m;
        $d = $today->diff($birthDate)->d;
        return $y." tahun ".$m." bulan ".$d." hari";
    }
}

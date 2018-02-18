<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pembayaran;

class TagihanSiswaController extends Controller
{
  public function index() {
    $tagihan = DB::table('pembayaran')
    ->join('jenis_pembayaran', 'pembayaran.idjenis_pembayaran', '=', 'jenis_pembayaran.idjenis_pembayaran')
    ->join('tahun_pelajaran', 'pembayaran.idtahun_pelajaran', '=', 'tahun_pelajaran.idtahun_pelajaran')
    ->join('siswa', 'pembayaran.nis', '=', 'siswa.nis')
    ->join('users', 'users.idusers', '=', 'siswa.idusers')
    ->select('pembayaran.*', 'jenis_pembayaran.nama_pembayaran', 'jenis_pembayaran.nominal', 'tahun_pelajaran.tahun_pelajaran')
    ->where([
      ['pembayaran.status', '=', 'belum_lunas'],
      ['siswa.nis', '=', session('id')],
    ])
    ->orderBy('pembayaran.tgl_tagihan','desc')
    ->get();

    $tagihan_count = $tagihan->count();
    $total_transaksi = $tagihan->sum('nominal');
    // dd($tagihan);

    return view('siswa/tagihan/index', [
      'tagihan' => $tagihan,
      'tagihan_count' => $tagihan_count,
      'total_transaksi' => $total_transaksi,
    ]);
  }

  public function bayar_tagihan(Request $request) {
    $tagihan = DB::table('pembayaran')
    ->join('jenis_pembayaran', 'pembayaran.idjenis_pembayaran', '=', 'jenis_pembayaran.idjenis_pembayaran')
    ->join('tahun_pelajaran', 'pembayaran.idtahun_pelajaran', '=', 'tahun_pelajaran.idtahun_pelajaran')
    ->join('siswa', 'pembayaran.nis', '=', 'siswa.nis')
    ->join('users', 'users.idusers', '=', 'siswa.idusers')
    ->select('users.*','pembayaran.*', 'jenis_pembayaran.nama_pembayaran', 'jenis_pembayaran.nominal', 'tahun_pelajaran.tahun_pelajaran')
    ->where([
      ['pembayaran.idpembayaran', '=', $request->id_tagihan],
      ['siswa.nis', '=', session('id')],
    ])
    ->orderBy('pembayaran.tgl_tagihan','desc')
    ->first();

    // dd($tagihan);

    return view('siswa/tagihan/pembayaran_tagihan', [
      'tagihan' => $tagihan,
    ]);
  }

  public function bayar_finpaycc(Request $request) {
    $curl = curl_init();

    $nama = $request->nama;
    $nis = $request->nis;
    $no_telp = $request->no_telp;
    // $no_telp = '085743411430';
    $nama_pembayaran = $request->nama_pembayaran;
    $tgl_tagihan = $request->tgl_tagihan;
    $nominal_format = number_format($request->nominal,0,0,'.');
    $nominal = $request->nominal;
    $tgl = date('d-m-Y');
    $pesan = "$nama, \nPembayaran \"$nama_pembayaran\" sebesar Rp.$nominal_format dilakukan pada $tgl \nTanggal tagihan : $tgl_tagihan";
    $cart_id = "cart" . $request->id_tagihan;

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.mainapi.net/finpay/2.0.0/transactions",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "ivp_method=create&ivp_store=19509&ivp_authkey=nkDCS-WLZg%5EXkzTB&ivp_amount=$nominal&ivp_currency=idr&ivp_test=0&ivp_cart=$cart_id&ivp_desc=$nama_pembayaran&return_auth=https%3A%2F%2Fmainapi.net%2Fauth.html&return_decl=https%3A%2F%2Fmainapi.net%2Fdecl.html&return_can=https%3A%2F%2Fmainapi.net%2Fcan.html",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer 4511ea753d09c092bcf35cda33ab6bf4",
        "Cache-Control: no-cache",
        "Content-Type: application/x-www-form-urlencoded",
        "Postman-Token: f7f20e1d-ad18-fb87-45cc-e03fd03490d2"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
      $request->session()->flash('pesan_flash', $err);
      // return redirect('admin/tagihan/');
    } else {
      echo $response;
      $response_json = json_decode($response);
      // dd($response_json);
      return redirect($response_json->order->url);

      // $request->session()->flash('pesan_flash', $response);
    }
  }

}

<?php

namespace Xprofit\PkRendeles\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use TableHelper;
use UserPermission;
use Menu;
use PhpHelper;
use Queries;
use Carbon\Carbon;
use App\Http\Controllers\AuthNeedController;
use View;
use \Illuminate\Pagination\LengthAwarePaginator;
use Exception;
use DataTable;

class RendelesController extends BaseController
{

  public function __construct()
  {
    parent::__construct();
  }

  public function rendLekerdezo ()
  {
    // menü
    $menu = new Menu();
    $menu->generate(Auth::user()->sm_munkatars_id);
    $menu->generateUserMenu(Auth::user()->sm_munkatars_id);

    $dbObj = DataTable::getDbObj('#tableRend', 'k_rendeles_tree.fmx');

    return view('pkrendeles::rendLekerdezo.rendLekerdezo', ['dbObj' => $dbObj]);
  } // rendLekerdezo

//

  public function tableRend (Request $request)
  {
    // ezt azért hagyom benn, mert így logból látszik, ha többször fut a lekérdező ajax... 1-szer kéne mindig csak...
    self::log("ajaxtableRend");

    $response = self::tableAlap($request,
    function (&$vissza)
    {
      $search = $vissza['search'];
      $list = DB::connection('oracle')->table('V_K_RENDELES_TREE v')
                    ->select('k_rendeles_id', 'iktatoszam', 'hatarido_tipus', 'pn_cim_nev', 'pn_cim_teljes',
                             DB::RAW("to_char(datum, 'yyyy.mm.dd') datum"),
                             DB::RAW("to_char(szallitasi_hatarido, 'yyyy.mm.dd') szallitasi_hatarido"),
                             DB::RAW("CASE WHEN jovahagyva_ = 0 THEN 'piros' END class_iktatoszam"),
                             DB::RAW("'R' reszlet"),
                             'k_gepjarmu_nev', 'belso_megjegyzes' );

     if (!empty($search['value']))
     {
       $filter = '%'.str_replace(' ', '%', Str::lower($search['value'])).'%';
       $list = $list->whereRaw("(lower(iktatoszam) LIKE ? OR
                                 lower(pn_cim_nev) LIKE ? OR
                                 lower(pn_cim_teljes) LIKE ? OR
                                 lower(k_gepjarmu_nev) LIKE ?)",[$filter, $filter, $filter, $filter]);
      }

      // session-be tenni a az összes szűrőt... az üres értékeket is el kell menteni...
      //session()->put('cikLekNevKereso', $filters['nevKereso']);

      // Megkap: $vissza['filters']  Visszaad: $vissza['list']
      $vissza['list'] = $list;
    },
    function (&$list)
    {
      foreach($list->items() as &$item)
      {
        $item->rendUrl  = action('\Xprofit\PkRendeles\Http\Controllers\RendelesController@rendKarb', ['k_rendeles_id' => $item->k_rendeles_id ]);
      }
    },
    true);

    return $response;
  } // tableRend

//

  public function rendKarb ($k_rendeles_id)
  {
    // menü
    $menu = new Menu();
    $menu->generate(Auth::user()->sm_munkatars_id);
    $menu->generateUserMenu(Auth::user()->sm_munkatars_id);

    $muvelet = empty($k_rendeles_id) ? "C" : "M";

    // jogosultság beállítása
    $hiba = DB::connection('oracle')->executeProcedure("Pk_User.set_userid", ['v_userid' => Auth::user()->sm_munkatars_id]);
  } // rendKarb

} // RendelesController

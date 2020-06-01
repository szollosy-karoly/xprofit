<?php

namespace Xprofit\PkRendeles\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Menu;
use App\Http\Controllers\AuthNeedController;
use View;
use \Illuminate\Pagination\LengthAwarePaginator;
use DataTable;
use PhpHelper;
use Validator;

class BaseController extends AuthNeedController
{

  public function ajaxFunction (Request $request)
  {
    $validatedDatas = $request->all();
    $obj = (array)json_decode($validatedDatas['obj']);
    $obj['params'] = (array)$obj['params'];

    $rules = [ 'func'       => 'required|string|max:30',
               'tipus'      => 'required|string|max:5',
               'params'     => 'nullable|array' ];

    $messages = [ 'required' => ':attribute megadása kötelező!',
                  'string' => ':attribute nem megfelelő!',
                  'numeric' => ':attribute csak szám lehet!',
                  'max'    => ':attribute hosszabb a megengedettnél!' ];

    $validator = Validator::make($obj, $rules, $messages);

    if ($validator->fails())
    {
      return response()->json(['error' => $validator->errors()->all()]);
    }

    if ($obj['tipus'] == 'P')
    {
      $message = DB::connection('oracle')->executeProcedure($obj['func'], $obj['params']);
    }
    else if ($obj['tipus'] == 'N')
    {
      $message = DB::connection('oracle')->executeFunction($obj['func'], $obj['params'], \PDO::PARAM_INT, 10);
    }
    else if ($obj['tipus'] == 'S')
    {
      $message = DB::connection('oracle')->executeFunction($obj['func'], $obj['params'], \PDO::PARAM_STR, 32000);
    }

    return response()->json(['message' => $message]);
  } // ajaxFunction

//

  public function vanIlyenJoga ($jog)
  {
    try
    {
      $n0 = DB::connection('oracle')->executeFunction('Pk_Jog.Van_Ilyen_Joga',
        ['pJog' => $jog, 'pSm_munkatars_id' => Auth::user()->sm_munkatars_id], \PDO::PARAM_INT, 10);

      return ($n0 == 1);
    }
    catch (Exception $e)
    {
    }

    return false;
  } // vanIlyenJoga

//

  public function getParameterErtek ($parameter)
  {
    try
    {
      $c0 = DB::connection('oracle')->executeFunction('Pk_Mind.Get_Parameter_Ertek',
        ['cParameter' => $parameter], \PDO::PARAM_STR, 4000);

      return $c0;
    }
    catch (Exception $e)
    {
    }

    return '';
  } // getParameterErtek

//

  protected function ezNullVolt ($pParam)
  {
    if ($pParam == null)
    {
      return ['value' => &$pParam, 'type' => \PDO::PARAM_NULL];
    }
    else
    {
      return $pParam;
    }
  } // ezNullVolt

//

  public function log ($szoveg)
  {
    // dekódolja a hiba verem "legősibb" kiváltó hibaüzenetét
    $hiba = DB::connection('oracle')->executeProcedure("Pk_Hiba.Log", ['pText' => $szoveg, 'pTipus' => 'L']);
  } // log

//

  public function arrayPaginator($page, $array, $request)
  {
    $perPage = 50;
    $offset = ($page * $perPage) - $perPage;
    return new LengthAwarePaginator(array_values(array_slice($array, $offset, $perPage, true)), count($array), $perPage, $page,
        ['path' => $request->url(), 'query' => $request->query()]);
  } // arrayPaginator

//

  public function select2Alap (Request $request, $func, $jogosultsag = false)
  {
    $page = $request->page ? $request->page : 1;
    $kereso = "%".str_replace(" ", "%", strtolower($request->q ? $request->q : '%'))."%";

    if ($jogosultsag)
    {
      $hiba = DB::connection('oracle')->executeProcedure("Pk_User.set_userid", ['v_userid' => Auth::user()->sm_munkatars_id]);
    }

    $result = $func($kereso, $request);

    $items = $this->arrayPaginator($page, $result, $request);
    $more = empty($result) ? false : ($items->lastPage() > $items->currentPage());

    return response()->json(['results' => $items->items(), 'pagination' => ['more' => $more]]);
  } // select2Alap

//

  public function ajaxResponse ()
  {
    $response = array();

    $validatedDatas = request()->validate([ 'draw'    => 'required|integer',
                                            'start'   => 'required|integer',
                                            'length'  => 'required|integer',
                                            'search'  => 'required',
                                            'columns' => 'required|array',
                                            'order'   => 'array',
                                            'filters' => 'array' ],
                                          [ 'required'    => ':attribute megadása kötelező.',
                                            'integer'     => ':attribute csak egész szám lehet.',
                                            'array'    => ':attribute csak tömb lehet ' ]);

    $draw   = $validatedDatas['draw'];
    $draw++;

    $start  = $validatedDatas['start'];  // hanyadik elemtől indul a lekérdezés pl. a 100.tól
    $length = $validatedDatas['length']; // hány elemre van szükség pl 25.
    if ($start == 0)
    {
      $validatedDatas['page'] = 1;
    }
    else
    {
      $validatedDatas['page'] = $start / $length + 1;
    }

    $response['draw'] = $draw;
    $response['validatedDatas'] = $validatedDatas;

    return $response;
  } // ajaxResponse

//

  public function tableAlap (Request $request, $func, $funcItems, $jogosultság = false)
  {
    $validatedDatas = request()->validate([ 'draw'    => 'required|integer',
                                            'start'   => 'required|integer',
                                            'length'  => 'required|integer',
                                            'search'  => 'nullable',
                                            'columns' => 'required|array',
                                            'order'   => 'array',
                                            'filters' => 'array' ],
                                          [ 'required'    => ':attribute megadása kötelező.',
                                            'integer'     => ':attribute csak egész szám lehet.',
                                            'array'    => ':attribute csak tömb lehet ' ]);

    $draw = $validatedDatas['draw'];
    $draw++;

    $start = $validatedDatas['start'];  // hanyadik elemtől indul a lekérdezés pl. a 100.tól
    $length = $validatedDatas['length']; // hány elemre van szükség pl 25.
    if ($start == 0)
    {
      $page = 1;
    }
    else
    {
      $page = $start / $length + 1;
    }

    $response = array();
    $response['draw'] = $draw;

    $filters = isset($validatedDatas['filters']) && !empty($validatedDatas['filters']) ? $validatedDatas['filters'] : [] ;
    $search = isset($validatedDatas['search']) && !empty($validatedDatas['search']) ? $validatedDatas['search'] : [] ;

    // Átad: $vissza['filters']  Visszaad: $vissza['list']
    $vissza['filters'] = $filters;
    $vissza['search'] = $search;

    $func($vissza);

    $list = $vissza['list'];

    // sorbarendezés
    if(!empty($validatedDatas['order']))
    {
      foreach($validatedDatas['order'] as $order)
      {
        $list = $list->orderBy($validatedDatas["columns"][$order['column']]['data'], $order['dir']);
      }
    }

    if ($jogosultság)
      $hiba = DB::connection('oracle')->executeProcedure("Pk_User.set_userid", ['v_userid' => Auth::user()->sm_munkatars_id]);

    $list = $list->paginate($length, ['*'], 'page', $page);

    $funcItems($list);
    $response['data'] = $list->items();
    $response['recordsFiltered'] = $list->total();

    return $response;
  } // tableAlap

  //

  public function reportParamUrl (Request $request)
  {
    try
    {
      $url = DB::connection('oracle')->executeFunction('Pk_Riport.Create_Param_Url',
        ['pRep_name' => $request->input('pRep_name'),
         'pPrinter' => $request->input('pPrinter') ?? null,
         'pSm_munkatars_id' => Auth::user()->sm_munkatars_id,
         'pPar1' => $request->input('pPar1') ?? null,
         'pErt1' => $request->input('pErt1') ?? null,
         'pPar2' => $request->input('pPar2') ?? null,
         'pErt2' => $request->input('pErt2') ?? null,
         'pPar3' => $request->input('pPar3') ?? null,
         'pErt3' => $request->input('pErt3') ?? null,
         'pPar4' => $request->input('pPar4') ?? null,
         'pErt4' => $request->input('pErt4') ?? null,
         'pPar5' => $request->input('pPar5') ?? null,
         'pErt5' => $request->input('pErt5') ?? null,
         'pPar6' => $request->input('pPar6') ?? null,
         'pErt6' => $request->input('pErt6') ?? null,
         'pPar7' => $request->input('pPar7') ?? null,
         'pErt7' => $request->input('pErt7') ?? null,
         'pPar8' => $request->input('pPar8') ?? null,
         'pErt8' => $request->input('pErt8') ?? null,
         'pPar9' => $request->input('pPar9') ?? null,
         'pErt9' => $request->input('pErt9') ?? null,
         'pPar10' => $request->input('pPar10') ?? null,
         'pErt10' => $request->input('pErt10') ?? null,
         'pPar11' => $request->input('pPar11') ?? null,
         'pErt11' => $request->input('pErt11') ?? null,
         'pPar12' => $request->input('pPar12') ?? null,
         'pErt12' => $request->input('pErt12') ?? null,
         'pPar13' => $request->input('pPar13') ?? null,
         'pErt13' => $request->input('pErt13') ?? null,
         'pPar14' => $request->input('pPar14') ?? null,
         'pErt14' => $request->input('pErt14') ?? null,
         'pPar15' => $request->input('pPar15') ?? null,
         'pErt15' => $request->input('pErt15') ?? null,
         'pPar16' => $request->input('pPar16') ?? null,
         'pErt16' => $request->input('pErt16') ?? null,
         'pPar17' => $request->input('pPar17') ?? null,
         'pErt17' => $request->input('pErt17') ?? null,
         'pPar18' => $request->input('pPar18') ?? null,
         'pErt18' => $request->input('pErt18') ?? null], \PDO::PARAM_STR, 32000);

      return response()->json(['url' => $url]);
    }
    catch (Exception $e)
    {
      $message = $this->hibaSzoveg($e->getMessage());
      return response()->json(['error' => $message]);
    }
  } // reportParamUrl

} // BaseController

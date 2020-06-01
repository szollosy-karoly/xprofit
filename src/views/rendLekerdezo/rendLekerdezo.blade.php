@extends('layouts.app')
@section('title')
Rendelés lekérdező
@endsection
@section('content')
  <!--   .wrapper-content tartalom: padding: 20px 10px 40px; -> 0px 0px 40px; -->
  <div class="wrapper wrapper-content animated fadeInRight" style="padding: 0px 0px 40px;">
    <div class="row">
      <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title p-1" style="min-height: 20px !important;">
              <div class="row">
                <div class="col-lg-6">
                  <h5>Rendelések</h5>
                </div>
                <div class="col-lg-6">
                  <button id="btUjRend" class="btn btn-xs btn-primary float-right mr-1" type="button" title="Új rendelés létrehozása"
                          onclick="window.location.href = '{{action('\Xprofit\PkRendeles\Http\Controllers\RendelesController@rendKarb', ['k_rendeles_id' => null])}}'">

                    <i class="fas fa-plus"></i>&nbsp;Új</button>
                </div>
              </div>
            </div>
            <div class="ibox-content p-1" >
                <div class="table-responsive">
                    <table id="tableRend" style="width:100%" class="table compact table-striped table-bordered table-hover">
                    </table>
                    {{-- most már js fogja hívni inkább... .select2 style-ok is kellenek!! --}}
                    <style>
                    .select2-close-mask{
                        z-index: 2099;
                    }
                    .select2-dropdown{
                        z-index: 3051;
                    }
                    </style>
                    <div class="modal inmodal modalForm" id="modalHivo" role="dialog">
                      <div class="modal-dialog modal-md">
                        <div class="modal-content animated flipInY">
                        </div>
                      </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
    </div>

    @push('scripts')

    <!-- Page-Level Scripts -->
    <script>
      var tableRend;

      $(document).ready(function()
      {
        // $dbObj átvétele controllerből
        var dbObj = {!! json_encode($dbObj) !!};

        // ki kell kapcsolni bal menüt js-el? hát most már nem, mert css-ben van kikapcsolva... mini-navbar class-al

        // előbb a select22 kell, mert ettől függhet datatable22
        tableRend = DataTable22({columns:   [{ data: "reszlet", title: "R",
                                               orderable: false,
                                               createdCell:  function (td, cellData, rowData, row, col)
                                               {
                                                 $(td).html('<button class="btn btn-xs p-0" title="Rendelés karbantartó megnyitása" '+
                                                            'onclick="window.location.href = \''+rowData.rendUrl+'\'" >'+
                                                            '<i class="fas fa-edit"></i></button>');
                                                 $(td).closest("tr").attr('data-k_rendeles_id', rowData.k_rendeles_id);
                                               }},
                                             { data: "iktatoszam", title: "Ikt", dataTitle: "Rendelés iktatószáma. Ebben a táblában a jogosultság szerinti rendelések jelennek csak meg." },
                                             { data: "datum", title:"Dátum", dataTitle: "Rendelés (rögzítés) dátuma" },
                                             { data: "szallitasi_hatarido", title: "Száll.hat.", dataTitle: "Szállítási határidő dátuma" },
                                             { data: "hatarido_tipus", title: "Igaz.", dataTitle: "Ez már egy igazolt szállítási határidő? Akkor 'I'." },
                                             { data: "pn_cim_nev", title: "Partner", dataTitle: "Partner neve" },
                                             { data: "pn_cim_teljes", title: "Cím", dataTitle: "Rendeléshez rögzített szállítási cím" },
                                             { data: "k_gepjarmu_nev", title: "Sofőr", dataTitle: "Vevő rendeléshez rögzített sofőr neve. Zöld, ha 'Tartós'" },
                                             { data: "belso_megjegyzes", title: "Megj", dataTitle: "Fejhez megadott belső megjegyzés" } ],
                               log: false,
                               aaSorting: ["datum,desc"],
                               dom: '<"html5buttons"B>lTfgtp',
                               searching: true,
                               pagingType: "simple_numbers",
                               scrollY: "300px",
                               scrollCollapse: true,
                               scrollX: true,
                               buttons: ["copy","csv","excel","pdf","print","adm"],
                               stateSave: true,
                               keys: { keys: [ 33 /*PgUp*/,34 /*PgDown*/,38 /* UP */, 40 /* DOWN */ ] },
                               url:  "{{ action('\Xprofit\PkRendeles\Http\Controllers\RendelesController@tableRend') }}"
                               /*filterFunc:   function ( d )
                                             {
                                               d.filters = { "nevKereso": $("#nevKereso").val() }
                                             }*/
                              }, dbObj);

        // navbar minimalizálás: itt kell újrarajzolni a fejléceket, ha lezajlott az animáció... TODO: meg kell várni, hogy animáció lefutott...
        $('.navbar-minimalize').on('click', function (event)
        {
            setTimeout(function ()
            { $.fn.dataTable
              .tables( { visible: true, api: true } )
              .columns.adjust();

              select2Szelesseg();
            }, 400);
        });

        // keresők végrehajtása
        $("#nevKereso").on('keyup', function(){
          tableRend.draw();
        });
      }); // document.ready


    </script>
    @endpush


  </div>
@endsection

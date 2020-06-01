<?php


Route::group(['namespace'  => 'Xprofit\PkRendeles\Http\Controllers',
              'middleware' => ['web', 'auth']],
              function () {

    // Rendelés
    Route::get('pk_rendeles/k_rendeles_tree.fmx',                                'RendelesController@rendLekerdezo');
    Route::get('pk_rendeles/k_rendeles_tree.fmx/tableRend',                      'RendelesController@tableRend');
    Route::get('pk_rendeles/k_rendeles_tree.fmx/rendKarb/{k_rendeles_id}',       'RendelesController@rendKarb');

});

?>

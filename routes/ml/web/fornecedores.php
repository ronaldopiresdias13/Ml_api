<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:api'], function () {
    Route::prefix('web')->group(function () {
        Route::prefix('compras')->group(function () {
            Route::get('fornecedores', 'Api\Web\Compras\FornecedoresController@getAllByEmpresaId');
            Route::post('fornecedores', 'Api\Web\Compras\FornecedoresController@store');
            Route::get('fornecedores/{fornecedor}', 'Api\Web\Compras\FornecedoresController@show');
            Route::put('fornecedores/{fornecedor}', 'Api\Web\Compras\FornecedoresController@update');
            Route::delete('fornecedores/{fornecedor}', 'Api\Web\Compras\FornecedoresController@destroy');
        });
    });
});

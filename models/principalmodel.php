<?php


// =====================================================
// ARCHIVO: models/principalmodel.php
// =====================================================
/**
 * Modelo Principal
 */
class PrincipalModel extends Model
{
    protected $table = 'inv_series';
    protected $primaryKey = 'id';
    protected $fillable = ['PEDIDO', 'serie', 'estado', 'fecha_creacion'];
    protected $hidden = [];

    public function __construct()
    {
        parent::__construct();
    }
}

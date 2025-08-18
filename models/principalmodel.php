<?php


class principalmodel extends Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function Cargar_Series($param)
    {
        try {
            $PEDIDO = $param["PEDIDOS_A_BUSCAR"];
            $SQL = "SELECT * FROM inv_series WHERE PEDIDO = :pedido";

            $query = $this->db->connect_dobra()->prepare($SQL);
            $query->bindParam(':pedido', $PEDIDO);
            if ($query->execute()) {
                $result = $query->fetchAll(PDO::FETCH_ASSOC);
                $res = array(
                    "success" => true,
                    "data" => $result,
                    "message" => "Series cargadas correctamente",
                    "pedido" => $PEDIDO
                );
                echo json_encode($res);
                exit();
            } else {
                $err = $query->errorInfo();
                $res = array(
                    "success" => false,
                    "message" => "Error al cargar las series",
                );
                echo json_encode($res);
                exit();
            }
        } catch (Exception $e) {
            $res = array(
                "success" => false,
                "message" => "Error al cargar las series: " . $e->getMessage(),
            );
            echo json_encode($res);
            exit();
        }
    }
}

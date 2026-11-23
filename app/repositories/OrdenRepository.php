<?php

/*
 * Proposito: Centraliza las consultas y transacciones de órdenes de trabajo.
 * Dependencias: /../../config/database.php.
 * Consumidores/dependientes: app\controllers\OrdenController.php, app\controllers\VehiculoController.php.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/OrdenCatalogo.php';

// Acceso a datos: concentra consultas y transacciones de órdenes.
/**
 * Encapsula las consultas preparadas y operaciones de persistencia de Orden.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class OrdenRepository
{
    private PDO $conexion;
    private const ESTADOS_ORDEN = [
        'registrada',
        'diagnostico',
        'en_reparacion',
        'pendiente_repuesto',
        'lista',
        'entregada',
        'cancelada',
    ];
    /**
     * Inicializa Orden y prepara sus dependencias.
     * Parametros: no recibe argumentos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? (new Database())->connect();
    }
    /**
     * Busca en la base de datos el precio vigente de una tarea del catalogo; falla si no existe.
     * Parametro $tarea: valor de tipo string recibido para esta operacion.
     * Devuelve: valor de tipo float, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function precioReferencial(string $tarea): float {
        if(!array_key_exists($tarea, OrdenCatalogo::preciosReferencialesTareas()))throw new InvalidArgumentException('La tarea no tiene una tarifa referencial configurada.');
        $stmt=$this->conexion->prepare('SELECT precio FROM tarifas_trabajos WHERE descripcion=:descripcion');
        $stmt->execute(['descripcion'=>$tarea]);
        $precio=$stmt->fetchColumn();
        if($precio===false)throw new InvalidArgumentException('La tarea no tiene una tarifa referencial configurada.');
        return (float)$precio;
    }
    /**
     * Consulta y devuelve el precio vigente de cada tarea del catalogo.
     * Parametros: no recibe argumentos.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function tarifasTareas(): array {
        $stmt=$this->conexion->prepare('SELECT descripcion, precio FROM tarifas_trabajos ORDER BY descripcion');
        $stmt->execute();
        $tarifas=[];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila){
            $tarifas[(string)$fila['descripcion']]=(float)$fila['precio'];
        }
        return $tarifas;
    }
    /**
     * Valida y actualiza en una transaccion los precios enviados; revierte todos los cambios si alguno falla.
     * Parametro $tarifas: valor de tipo array recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function guardarTarifasTareas(array $tarifas): void {
        $catalogo=OrdenCatalogo::preciosReferencialesTareas();
        $this->conexion->beginTransaction();
        try {
            $actualizar=$this->conexion->prepare(
                'UPDATE tarifas_trabajos SET precio=:precio WHERE descripcion=:descripcion'
            );
            foreach($tarifas as $descripcion=>$precio){
                if(!is_string($descripcion)||!array_key_exists($descripcion,$catalogo)||!is_numeric($precio)||(float)$precio<0){
                    throw new InvalidArgumentException('Una de las tarifas enviadas no es válida.');
                }
                $actualizar->execute(['precio'=>(float)$precio,'descripcion'=>$descripcion]);
                if($actualizar->rowCount()===0){
                    $verificar=$this->conexion->prepare('SELECT COUNT(*) FROM tarifas_trabajos WHERE descripcion=:descripcion');
                    $verificar->execute(['descripcion'=>$descripcion]);
                    if((int)$verificar->fetchColumn()===0)throw new InvalidArgumentException('No se encontró una tarifa del catálogo.');
                }
            }
            $this->conexion->commit();
        } catch(Throwable $e){
            if($this->conexion->inTransaction())$this->conexion->rollBack();
            throw $e;
        }
    }
    /**
     * Consulta los mecanicos activos que se pueden asignar a una orden.
     * Parametros: no recibe argumentos.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    public function mecanicos(): array {
        $s=$this->conexion->prepare('SELECT id,nombre FROM mecanicos WHERE activo=1 ORDER BY nombre');
        $s->execute();
        return $s->fetchAll();
    }
    /**
     * Consulta y devuelve los registros de Orden que coinciden con los filtros recibidos.
     * Parametro $busqueda: texto usado para buscar coincidencias en los registros.
     * Parametro $soloAsignadas: valor de tipo bool recibido para esta operacion.
     * Parametro $mecanico: mecanico que se verifica o asigna a la orden.
     * Parametro $usuarioId: identificador de la cuenta vinculada a la operacion.
     * Parametro $estado: nuevo estado que se valida o persiste.
     * Parametro $fechaDesde: limite inicial del periodo de consulta.
     * Parametro $fechaHasta: limite final del periodo de consulta.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    public function listar(
        string $busqueda = '',
        bool $soloAsignadas = false,
        string $mecanico = '',
        int $usuarioId = 0,
        ?string $estado = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {
        // Combina filtros de búsqueda, estado y usuario cuando se trata de mecánicos.
        $sql='SELECT DISTINCT o.*,v.placa,v.marca,v.modelo,c.nombre AS cliente FROM ordenes_trabajo o JOIN vehiculos v ON v.id=o.vehiculo_id JOIN clientes c ON c.id=v.cliente_id';
        if ($soloAsignadas) $sql .= ' JOIN orden_mecanicos om ON om.orden_id=o.id JOIN mecanicos m ON m.id=om.mecanico_id';
        $condiciones=[]; $params=[];
        if ($soloAsignadas) {
            $condiciones[]='(m.usuario_id=:usuario_id'.($mecanico !== '' ? ' OR (m.usuario_id IS NULL AND m.nombre=:mecanico)' : '').')';
            $params['usuario_id']=$usuarioId;
            if ($mecanico !== '') $params['mecanico']=$mecanico;
        }
        if($busqueda!==''){
            $condiciones[]='(o.codigo LIKE :codigo OR c.nombre LIKE :cliente OR v.placa LIKE :placa OR v.marca LIKE :marca OR v.modelo LIKE :modelo)';
            $termino='%'.$busqueda.'%';
            foreach(['codigo','cliente','placa','marca','modelo'] as $campo)$params[$campo]=$termino;
        }
        if($estado!==null){
            $condiciones[]='o.estado=:estado';
            $params['estado']=$estado;
        }
        if ($fechaDesde !== null) {
            $condiciones[] = 'o.fecha_ingreso >= :fecha_desde';
            $params['fecha_desde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta !== null) {
            $condiciones[] = 'o.fecha_ingreso < :fecha_hasta';
            $params['fecha_hasta'] = (new DateTimeImmutable($fechaHasta))
                ->modify('+1 day')
                ->format('Y-m-d 00:00:00');
        }
        if($condiciones)$sql.=' WHERE '.implode(' AND ',$condiciones);
        $sql.=' ORDER BY o.fecha_ingreso DESC';$s=$this->conexion->prepare($sql);$s->execute($params);return $s->fetchAll();
    }
    /**
     * Consulta y devuelve los vehiculos del periodo seleccionado.
     * Parametros: no recibe argumentos.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    public function vehiculos(): array {$s=$this->conexion->prepare('SELECT v.*,c.nombre cliente FROM vehiculos v JOIN clientes c ON c.id=v.cliente_id ORDER BY v.placa');$s->execute();return $s->fetchAll(PDO::FETCH_ASSOC);}
    /**
     * Comprueba si el mecanico o usuario indicado esta asignado a la orden.
     * Parametro $orden: identificador de la orden de trabajo.
     * Parametro $mecanico: mecanico que se verifica o asigna a la orden.
     * Parametro $usuarioId: identificador de la cuenta vinculada a la operacion.
     * Devuelve: valor de tipo bool, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    public function asignadaA(int $orden, string $mecanico, int $usuarioId=0): bool {
        // Esta comprobación también se usa para autorizar cambios sobre una OT.
        $s=$this->conexion->prepare('SELECT 1 FROM orden_mecanicos om JOIN mecanicos m ON m.id=om.mecanico_id WHERE om.orden_id=:orden AND (m.usuario_id=:usuario_id OR (m.usuario_id IS NULL AND m.nombre=:mecanico)) LIMIT 1');
        $s->execute(['orden'=>$orden,'usuario_id'=>$usuarioId,'mecanico'=>$mecanico]);
        return (bool) $s->fetchColumn();
    }
    /**
     * Inserta un nuevo registro de Orden con los datos validados.
     * Parametro $d: arreglo con los datos validados para crear la orden.
     * Devuelve: valor de tipo int, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function crear(array $d): int {
        // Orden, historial inicial y asignaciones se guardan dentro de una transacción.
        $this->conexion->beginTransaction();
        try {
            $code='OT-'.date('Ymd').'-'.random_int(100,999);
            $s=$this->conexion->prepare('INSERT INTO ordenes_trabajo (codigo,vehiculo_id,usuario_id,fecha_ingreso,fecha_prevista_retorno,diagnostico,observaciones,estado) VALUES (:code,:vehicle,:user,:ingreso,:retorno,:diagnostico,:observaciones,\'registrada\')');
            $s->execute(['code'=>$code,'vehicle'=>$d['vehiculo'],'user'=>$d['usuario'],'ingreso'=>$d['ingreso'],'retorno'=>$d['retorno'],'diagnostico'=>$d['diagnostico'],'observaciones'=>$d['observaciones']]);
            $id=(int)$this->conexion->lastInsertId();
            $h=$this->conexion->prepare('INSERT INTO historial_estados (orden_id,usuario_id,estado_anterior,estado_nuevo,observaciones) VALUES (:o,:u,NULL,\'registrada\',\'Orden creada\')');
            $h->execute(['o'=>$id,'u'=>$d['usuario']]);
            $a=$this->conexion->prepare('INSERT INTO orden_mecanicos (orden_id,mecanico_id) VALUES (:orden,:mecanico)');
            $validar=$this->conexion->prepare('SELECT COUNT(*) FROM mecanicos WHERE id=:id AND activo=1');
            foreach (($d['mecanicos'] ?? []) as $mecanicoId) {
                $validar->execute(['id'=>$mecanicoId]);
                if ((int)$validar->fetchColumn()!==1) throw new InvalidArgumentException('El mecánico seleccionado no está disponible.');
                $a->execute(['orden'=>$id,'mecanico'=>$mecanicoId]);
            }
            $this->conexion->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            throw $e;
        }
    }
    /**
     * Consulta la orden y agrupa sus datos de vehiculo, cliente, tareas, pagos, historial y mecanicos asignados.
     * Parametro $id: identificador del registro que se consulta o modifica.
     * Devuelve: valor de tipo ?array, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    public function detalle(int $id): ?array {
        // Reúne datos de la orden y sus listas relacionadas para una sola ficha.
        $s=$this->conexion->prepare('SELECT o.*,v.placa,v.marca,v.modelo,v.anio,c.nombre cliente,c.telefono,c.direccion FROM ordenes_trabajo o JOIN vehiculos v ON v.id=o.vehiculo_id JOIN clientes c ON c.id=v.cliente_id WHERE o.id=:id LIMIT 1');$s->execute(['id'=>$id]);$o=$s->fetch();if(!$o)return null;$base=['orden'=>$o];
        foreach(['tareas'=>'SELECT * FROM tareas_orden WHERE orden_id=:id ORDER BY id DESC','pagos'=>'SELECT p.*,u.nombre usuario FROM pagos p JOIN usuarios u ON u.id=p.usuario_id WHERE p.orden_id=:id ORDER BY p.fecha_pago DESC','historial'=>'SELECT h.*,u.nombre usuario FROM historial_estados h JOIN usuarios u ON u.id=h.usuario_id WHERE h.orden_id=:id ORDER BY h.fecha_cambio DESC','repuestos'=>'SELECT ro.id,ro.repuesto_id,ro.cantidad,ro.precio_unitario,r.codigo,r.nombre FROM repuestos_orden ro JOIN repuestos r ON r.id=ro.repuesto_id WHERE ro.orden_id=:id ORDER BY ro.id DESC'] as $key=>$sql){$q=$this->conexion->prepare($sql);$q->execute(['id'=>$id]);$base[$key]=$q->fetchAll(PDO::FETCH_ASSOC);}
        $q=$this->conexion->prepare('SELECT mecanico_id FROM orden_mecanicos WHERE orden_id=:id');
        $q->execute(['id'=>$id]);
        $base['mecanicos_asignados']=$q->fetchAll(PDO::FETCH_COLUMN);
        $q=$this->conexion->prepare('SELECT m.id,m.nombre FROM orden_mecanicos om JOIN mecanicos m ON m.id=om.mecanico_id WHERE om.orden_id=:id ORDER BY m.nombre');
        $q->execute(['id'=>$id]);
        $base['mecanicos']=$q->fetchAll();
        return $base;
    }
    /**
     * Actualiza el estado de la orden, sincroniza el estado del vehiculo y registra el historial dentro de una transaccion.
     * Parametro $id: identificador del registro que se consulta o modifica.
     * Parametro $usuario: identificador del usuario que realiza la operacion.
     * Parametro $estado: nuevo estado que se valida o persiste.
     * Parametro $observacion: texto que se agrega al historial como observacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    public function estado(int $id,int $usuario,string $estado,string $observacion): void {
        $this->validarEstado($estado);
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT estado, vehiculo_id FROM ordenes_trabajo WHERE id = :id FOR UPDATE'
            );
            $consulta->execute(['id' => $id]);
            $actual = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new InvalidArgumentException('Orden no encontrada.');
            }

            $actualizarOrden = $this->conexion->prepare(
                'UPDATE ordenes_trabajo
                 SET estado = :estado,
                     fecha_entrega = CASE WHEN :estado_entrega = \'entregada\'
                         THEN CURRENT_TIMESTAMP ELSE fecha_entrega END
                 WHERE id = :id'
            );
            $actualizarOrden->execute([
                'estado' => $estado,
                'estado_entrega' => $estado,
                'id' => $id,
            ]);

            $estadoVehiculo = match ($estado) {
                'registrada' => 'registrado',
                'diagnostico' => 'en_revision',
                'en_reparacion' => 'en_reparacion',
                'pendiente_repuesto' => 'pendiente_repuesto',
                'lista' => 'listo',
                'entregada' => 'entregado',
                default => null,
            };
            if ($estadoVehiculo !== null) {
                $actualizarVehiculo = $this->conexion->prepare(
                    'UPDATE vehiculos SET estado = :estado WHERE id = :vehiculo'
                );
                $actualizarVehiculo->execute([
                    'estado' => $estadoVehiculo,
                    'vehiculo' => $actual['vehiculo_id'],
                ]);
            }

            $historial = $this->conexion->prepare(
                'INSERT INTO historial_estados
                    (orden_id, usuario_id, estado_anterior, estado_nuevo, observaciones)
                 VALUES (:orden, :usuario, :anterior, :nuevo, :observaciones)'
            );
            $historial->execute([
                'orden' => $id,
                'usuario' => $usuario,
                'anterior' => $actual['estado'],
                'nuevo' => $estado,
                'observaciones' => mb_substr($observacion, 0, 255, 'UTF-8'),
            ]);
            $this->conexion->commit();
        } catch (Throwable $exception) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $exception;
        }
    }
    /**
     * Actualiza diagnostico, observaciones y fecha prevista de retorno, y registra el cambio tecnico atomicamente.
     * Parametro $id: identificador del registro que se consulta o modifica.
     * Parametro $diagnostico: diagnostico que se registra en la orden.
     * Parametro $observaciones: observaciones tecnicas asociadas a la orden.
     * Parametro $retorno: fecha prevista de retorno del vehiculo.
     * Parametro $usuario: identificador del usuario que realiza la operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function actualizarTecnico(int $id,string $diagnostico,string $observaciones,?string $retorno,int $usuario): void {
        $this->conexion->beginTransaction();
        try {
            $validar=$this->conexion->prepare('SELECT id FROM ordenes_trabajo WHERE id=:id FOR UPDATE');
            $validar->execute(['id'=>$id]);
            if(!$validar->fetchColumn())throw new InvalidArgumentException('Orden no encontrada.');
            $actualizar=$this->conexion->prepare('UPDATE ordenes_trabajo SET diagnostico=:diagnostico,observaciones=:observaciones,fecha_prevista_retorno=:retorno WHERE id=:id');
            $actualizar->execute(['diagnostico'=>$diagnostico,'observaciones'=>$observaciones,'retorno'=>$retorno,'id'=>$id]);
            $estado=$this->conexion->prepare('SELECT estado FROM ordenes_trabajo WHERE id=:id');
            $estado->execute(['id'=>$id]);
            $valor=(string)$estado->fetchColumn();
            $historial=$this->conexion->prepare('INSERT INTO historial_estados (orden_id,usuario_id,estado_anterior,estado_nuevo,observaciones) VALUES (:id,:usuario,:estado,:estado_nuevo,:observacion)');
            $historial->execute(['id'=>$id,'usuario'=>$usuario,'estado'=>$valor,'estado_nuevo'=>$valor,'observacion'=>'Actualización técnica registrada por el mecánico']);
            $this->conexion->commit();
        } catch(Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }
    /**
     * Actualiza los campos de la orden y sus asignaciones de mecanicos segun los datos validados.
     * Parametro $id: identificador del registro que se consulta o modifica.
     * Parametro $vehiculo: identificador del vehiculo asociado a la orden.
     * Parametro $retorno: fecha prevista de retorno del vehiculo.
     * Parametro $estado: nuevo estado que se valida o persiste.
     * Parametro $diagnostico: diagnostico que se registra en la orden.
     * Parametro $observaciones: observaciones tecnicas asociadas a la orden.
     * Parametro $costo: costo validado de la tarea.
     * Parametro $mecanicos: identificadores de mecanicos que se asignaran a la orden.
     * Parametro $usuario: identificador del usuario que realiza la operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    public function editar(int $id,int $vehiculo,?string $retorno,string $estado,string $diagnostico,string $observaciones,?float $costo,array $mecanicos,int $usuario): void {
        $this->validarEstado($estado);
        if ($costo !== null && (!is_finite($costo) || $costo < 0)) {
            throw new InvalidArgumentException('El costo final no puede ser negativo.');
        }
        $this->conexion->beginTransaction();
        try {
            $actual=$this->conexion->prepare('SELECT estado FROM ordenes_trabajo WHERE id=:id FOR UPDATE');
            $actual->execute(['id'=>$id]);
            $anterior=$actual->fetchColumn();
            if($anterior===false)throw new InvalidArgumentException('Orden no encontrada.');
            $editar=$this->conexion->prepare(
                'UPDATE ordenes_trabajo
                 SET vehiculo_id = :vehiculo, fecha_prevista_retorno = :retorno,
                     estado = :estado,
                     fecha_entrega = CASE WHEN :estado_entrega = \'entregada\'
                         THEN COALESCE(fecha_entrega, CURRENT_TIMESTAMP) ELSE fecha_entrega END,
                     diagnostico = :diagnostico, observaciones = :observaciones,
                     costo_final = COALESCE(:costo, costo_final)
                 WHERE id = :id'
            );
            $editar->execute([
                'vehiculo' => $vehiculo,
                'retorno' => $retorno,
                'estado' => $estado,
                'estado_entrega' => $estado,
                'diagnostico' => $diagnostico,
                'observaciones' => $observaciones,
                'costo' => $costo,
                'id' => $id,
            ]);
            $validar=$this->conexion->prepare('SELECT COUNT(*) FROM mecanicos WHERE id=:id AND activo=1');
            $insertar=$this->conexion->prepare('INSERT INTO orden_mecanicos (orden_id,mecanico_id) VALUES (:orden,:mecanico)');
            $this->conexion->prepare('DELETE FROM orden_mecanicos WHERE orden_id=:id')->execute(['id'=>$id]);
            foreach(array_values(array_unique($mecanicos)) as $mecanicoId){
                $validar->execute(['id'=>$mecanicoId]);
                if((int)$validar->fetchColumn()!==1)throw new InvalidArgumentException('El mecánico seleccionado no está disponible.');
                $insertar->execute(['orden'=>$id,'mecanico'=>$mecanicoId]);
            }
            if((string)$anterior!==$estado){
                $historial=$this->conexion->prepare('INSERT INTO historial_estados (orden_id,usuario_id,estado_anterior,estado_nuevo,observaciones) VALUES (:orden,:usuario,:anterior,:nuevo,:observacion)');
                $historial->execute(['orden'=>$id,'usuario'=>$usuario,'anterior'=>$anterior,'nuevo'=>$estado,'observacion'=>'Orden editada por administrador']);
            }
            $this->conexion->commit();
        } catch(Throwable $e){if($this->conexion->inTransaction())$this->conexion->rollBack();throw $e;}
    }
    /**
     * Actualiza el precio confirmado de la orden conforme a la operacion solicitada.
     * Parametro $orden: identificador de la orden de trabajo.
     * Parametro $precio: precio que se desea consultar o actualizar.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function actualizarPrecio(int $orden,float $precio): void {
        if (!is_finite($precio) || $precio < 0) {
            throw new InvalidArgumentException('El precio confirmado debe ser mayor o igual que cero.');
        }
        $validar=$this->conexion->prepare('SELECT id FROM ordenes_trabajo WHERE id=:id LIMIT 1');
        $validar->execute(['id'=>$orden]);
        if(!$validar->fetchColumn())throw new InvalidArgumentException('Orden no encontrada.');
        $actualizar=$this->conexion->prepare('UPDATE ordenes_trabajo SET costo_final=:precio,precio_confirmado=1 WHERE id=:id');
        $actualizar->execute(['precio'=>$precio,'id'=>$orden]);
    }
    /**
     * Elimina o desactiva el registro de Orden conforme a las reglas de persistencia.
     * Parametro $id: identificador del registro que se consulta o modifica.
     * Parametro $usuario: identificador del usuario que realiza la operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function eliminar(int $id,int $usuario): void {
        $this->conexion->beginTransaction();
        try {
            $validar=$this->conexion->prepare('SELECT id FROM ordenes_trabajo WHERE id=:id FOR UPDATE');
            $validar->execute(['id'=>$id]);
            if(!$validar->fetchColumn())throw new InvalidArgumentException('Orden no encontrada.');

            $partidas = $this->conexion->prepare(
                'SELECT repuesto_id, cantidad, precio_unitario FROM repuestos_orden
                 WHERE orden_id = :id FOR UPDATE'
            );
            $partidas->execute(['id' => $id]);
            $reponer = $this->conexion->prepare(
                'UPDATE repuestos SET stock = stock + :cantidad WHERE id = :id'
            );
            $registrarEntrada = $this->conexion->prepare(
                'INSERT INTO movimientos_inventario
                    (repuesto_id, usuario_id, orden_id, tipo, cantidad, observaciones)
                 VALUES (:repuesto_id, :usuario_id, :orden_id, :tipo, :cantidad, :observaciones)'
            );
            foreach ($partidas->fetchAll(PDO::FETCH_ASSOC) as $partida) {
                $reponer->execute([
                    'cantidad' => (int) $partida['cantidad'],
                    'id' => (int) $partida['repuesto_id'],
                ]);
                $registrarEntrada->execute([
                    'repuesto_id' => (int) $partida['repuesto_id'],
                    'usuario_id' => $usuario,
                    'orden_id' => $id,
                    'tipo' => 'entrada',
                    'cantidad' => (int) $partida['cantidad'],
                    'observaciones' => 'Repuesto reintegrado por eliminación de orden',
                ]);
            }

            $desvincularMovimientos = $this->conexion->prepare(
                'UPDATE movimientos_inventario SET orden_id = NULL WHERE orden_id = :id'
            );
            $desvincularMovimientos->execute(['id' => $id]);

            foreach(['historial_estados','pagos','repuestos_orden','tareas_orden','orden_mecanicos'] as $tabla){
                $sql=$this->conexion->prepare("DELETE FROM {$tabla} WHERE orden_id=:id");
                $sql->execute(['id'=>$id]);
            }
            $eliminar=$this->conexion->prepare('DELETE FROM ordenes_trabajo WHERE id=:id');
            $eliminar->execute(['id'=>$id]);
            $this->conexion->commit();
        } catch(Throwable $e){if($this->conexion->inTransaction())$this->conexion->rollBack();throw $e;}
    }
    /**
     * Valida las tareas seleccionadas contra el catalogo y las agrega a la orden con sus precios.
     * Parametro $orden: identificador de la orden de trabajo.
     * Parametro $tareas: tareas seleccionadas para asociarlas a la orden.
     * Devuelve: valor de tipo int, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    public function agregarTareasCatalogo(int $orden,array $tareas): int {
        // Al agregar tareas se guardan sus precios actuales; cambios futuros del
        // catálogo no alteran lo que ya se había cotizado en esta orden.
        $this->conexion->beginTransaction();
        try {
            $validarOrden=$this->conexion->prepare('SELECT id FROM ordenes_trabajo WHERE id=:orden FOR UPDATE');
            $validarOrden->execute(['orden'=>$orden]);
            if(!$validarOrden->fetchColumn())throw new InvalidArgumentException('Orden no encontrada.');
            $buscar=$this->conexion->prepare('SELECT id,costo FROM tareas_orden WHERE orden_id=:orden AND descripcion=:descripcion LIMIT 1');
            $insertar=$this->conexion->prepare('INSERT INTO tareas_orden (orden_id,descripcion,costo,estado) VALUES (:orden,:descripcion,:costo,\'pendiente\')');
            $actualizarTarea=$this->conexion->prepare('UPDATE tareas_orden SET costo=:costo WHERE id=:id AND orden_id=:orden AND costo IS NULL');
            $agregadas=0;
            $montoAgregado=0.0;
            foreach(array_values(array_unique($tareas)) as $tarea){
                $buscar->execute(['orden'=>$orden,'descripcion'=>$tarea]);
                $precio=$this->precioReferencial($tarea);
                $existente=$buscar->fetch(PDO::FETCH_ASSOC);
                if($existente){
                    if($existente['costo']!==null)continue;
                    $actualizarTarea->execute(['costo'=>$precio,'id'=>$existente['id'],'orden'=>$orden]);
                    if($actualizarTarea->rowCount()>0){$agregadas++;$montoAgregado+=$precio;}
                    continue;
                }
                $insertar->execute(['orden'=>$orden,'descripcion'=>$tarea,'costo'=>$precio]);
                $agregadas++;
                $montoAgregado+=$precio;
            }
            if($agregadas>0){
                $actualizarPrecio=$this->conexion->prepare('UPDATE ordenes_trabajo SET costo_final=COALESCE(costo_final,0)+:monto,precio_confirmado=0 WHERE id=:orden');
                $actualizarPrecio->execute(['monto'=>$montoAgregado,'orden'=>$orden]);
            }
            $this->conexion->commit();
            return $agregadas;
        } catch(Throwable $e){if($this->conexion->inTransaction())$this->conexion->rollBack();throw $e;}
    }
    /**
     * Marca como completadas las tareas seleccionadas que pertenecen a la orden indicada.
     * Parametro $orden: identificador de la orden de trabajo.
     * Parametro $ids: identificadores de las tareas o registros que se procesaran.
     * Devuelve: valor de tipo int, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    public function completarTareas(int $orden,array $ids): int {
        // Verifica que todos los IDs seleccionados pertenezcan a la orden indicada.
        $ids=array_values(array_unique(array_filter(array_map('intval',$ids),function($id){return $id>0;})));
        if(!$ids)throw new InvalidArgumentException('Seleccione al menos un trabajo.');
        $this->conexion->beginTransaction();
        try {
            $validarOrden=$this->conexion->prepare('SELECT id FROM ordenes_trabajo WHERE id=:orden FOR UPDATE');
            $validarOrden->execute(['orden'=>$orden]);
            if(!$validarOrden->fetchColumn())throw new InvalidArgumentException('Orden no encontrada.');
            $marcadores=implode(',',array_fill(0,count($ids),'?'));
            $validar=$this->conexion->prepare("SELECT COUNT(*) FROM tareas_orden WHERE orden_id=? AND id IN ({$marcadores})");
            $validar->execute(array_merge([$orden],$ids));
            if((int)$validar->fetchColumn()!==count($ids))throw new InvalidArgumentException('Una o más tareas no pertenecen a esta orden.');
            $actualizar=$this->conexion->prepare("UPDATE tareas_orden SET estado='completada' WHERE orden_id=? AND id IN ({$marcadores})");
            $actualizar->execute(array_merge([$orden],$ids));
            $completadas=$actualizar->rowCount();
            $this->conexion->commit();
            return $completadas;
        } catch(Throwable $e){if($this->conexion->inTransaction())$this->conexion->rollBack();throw $e;}
    }
    /**
     * Resume las tareas de la orden, incluyendo cantidades y costos agregados.
     * Parametro $orden: identificador de la orden de trabajo.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    public function resumenTareas(int $orden): array {
        // Resume total, pendientes y monto para decidir si el técnico puede terminar.
        $s=$this->conexion->prepare("SELECT COUNT(*) AS total,SUM(CASE WHEN estado<>'completada' THEN 1 ELSE 0 END) AS pendientes,COALESCE(SUM(costo),0) AS monto FROM tareas_orden WHERE orden_id=:orden");
        $s->execute(['orden'=>$orden]);
        return $s->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'pendientes'=>0,'monto'=>0];
    }
    /**
     * Registra una tarea individual asociada a la orden con estado y costo.
     * Parametro $orden: identificador de la orden de trabajo.
     * Parametro $descripcion: descripcion de la tarea o concepto que se registra.
     * Parametro $estado: nuevo estado que se valida o persiste.
     * Parametro $costo: costo validado de la tarea.
     * Devuelve: valor de tipo bool, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    public function tarea(int $orden,string $descripcion,string $estado,$costo): bool {
        if (!in_array($estado, ['pendiente', 'en_proceso', 'completada'], true)) {
            throw new InvalidArgumentException('El estado de la tarea no es válido.');
        }
        if ($costo !== null && (!is_numeric($costo) || !is_finite((float) $costo) || (float) $costo < 0)) {
            throw new InvalidArgumentException('El costo de la tarea debe ser mayor o igual que cero.');
        }
        $this->conexion->beginTransaction();
        try {
            $s=$this->conexion->prepare('INSERT INTO tareas_orden (orden_id,descripcion,costo,estado) VALUES (:orden,:descripcion,:costo,:estado)');
            $guardada=$s->execute(['orden'=>$orden,'descripcion'=>mb_substr($descripcion,0,255,'UTF-8'),'costo'=>$costo,'estado'=>$estado]);
            if($guardada&&$costo!==null){
                $actualizar=$this->conexion->prepare('UPDATE ordenes_trabajo SET costo_final=COALESCE(costo_final,0)+:costo,precio_confirmado=0 WHERE id=:orden');
                $actualizar->execute(['costo'=>$costo,'orden'=>$orden]);
            }
            $this->conexion->commit();
            return $guardada;
        } catch(Throwable $e){if($this->conexion->inTransaction())$this->conexion->rollBack();throw $e;}
    }
    /**
     * Registra un pago de la orden con usuario, importe, metodo, referencia y observacion.
     * Parametro $orden: identificador de la orden de trabajo.
     * Parametro $usuario: identificador del usuario que realiza la operacion.
     * Parametro $monto: valor de tipo float recibido para esta operacion.
     * Parametro $metodo: metodo de pago elegido.
     * Parametro $referencia: referencia externa asociada al pago.
     * Parametro $observacion: texto que se agrega al historial como observacion.
     * Devuelve: valor de tipo bool, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos; puede persistir cambios en la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function pago(int $orden,int $usuario,float $monto,string $metodo,string $referencia,string $observacion): bool
    {
        if (!is_finite($monto) || $monto <= 0) {
            throw new InvalidArgumentException('El monto del pago debe ser mayor que cero.');
        }
        if (!in_array($metodo, ['efectivo', 'transferencia', 'otro'], true)) {
            throw new InvalidArgumentException('El método de pago no es válido.');
        }
        $s=$this->conexion->prepare('INSERT INTO pagos (orden_id,usuario_id,monto,metodo,referencia,observaciones) VALUES (:orden,:usuario,:monto,:metodo,:referencia,:observaciones)');
        return $s->execute(['orden'=>$orden,'usuario'=>$usuario,'monto'=>$monto,'metodo'=>$metodo,'referencia'=>mb_substr($referencia,0,100,'UTF-8'),'observaciones'=>mb_substr($observacion,0,255,'UTF-8')]);
    }

    public function agregarRepuesto(int $orden, int $repuesto, int $cantidad, int $usuario, float $precioUnitario): int
    {
        if ($cantidad <= 0 || !is_finite($precioUnitario) || $precioUnitario < 0) {
            throw new InvalidArgumentException('La cantidad o el precio del repuesto no son válidos.');
        }
        $precioUnitario = round($precioUnitario, 2);

        $this->conexion->beginTransaction();
        try {
            $ordenConsulta = $this->conexion->prepare(
                'SELECT id FROM ordenes_trabajo WHERE id = :id FOR UPDATE'
            );
            $ordenConsulta->execute(['id' => $orden]);
            if (!$ordenConsulta->fetchColumn()) {
                throw new InvalidArgumentException('Orden no encontrada.');
            }

            $repuestoConsulta = $this->conexion->prepare(
                'SELECT stock, activo FROM repuestos WHERE id = :id FOR UPDATE'
            );
            $repuestoConsulta->execute(['id' => $repuesto]);
            $datosRepuesto = $repuestoConsulta->fetch(PDO::FETCH_ASSOC);
            if (!$datosRepuesto || (int) $datosRepuesto['activo'] !== 1) {
                throw new InvalidArgumentException('El repuesto no está disponible.');
            }
            if ((int) $datosRepuesto['stock'] < $cantidad) {
                throw new InvalidArgumentException('No hay existencias suficientes para agregar el repuesto.');
            }

            $actualizarStock = $this->conexion->prepare(
                'UPDATE repuestos SET stock = stock - :cantidad WHERE id = :id'
            );
            $actualizarStock->execute(['cantidad' => $cantidad, 'id' => $repuesto]);

            $insertarDetalle = $this->conexion->prepare(
                'INSERT INTO repuestos_orden (orden_id, repuesto_id, cantidad, precio_unitario)
                 VALUES (:orden, :repuesto, :cantidad, :precio)'
            );
            $insertarDetalle->execute([
                'orden' => $orden,
                'repuesto' => $repuesto,
                'cantidad' => $cantidad,
                'precio' => $precioUnitario,
            ]);
            $detalleId = (int) $this->conexion->lastInsertId();

            $movimiento = $this->conexion->prepare(
                'INSERT INTO movimientos_inventario
                    (repuesto_id, usuario_id, orden_id, tipo, cantidad, observaciones)
                 VALUES (:repuesto, :usuario, :orden, :tipo, :cantidad, :observaciones)'
            );
            $movimiento->execute([
                'repuesto' => $repuesto,
                'usuario' => $usuario,
                'orden' => $orden,
                'tipo' => 'salida',
                'cantidad' => $cantidad,
                'observaciones' => 'Repuesto asignado a orden de trabajo',
            ]);

            $actualizarCosto = $this->conexion->prepare(
                'UPDATE ordenes_trabajo
                 SET costo_final = COALESCE(costo_final, 0) + :costo, precio_confirmado = 0
                 WHERE id = :id'
            );
            $actualizarCosto->execute([
                'costo' => $cantidad * $precioUnitario,
                'id' => $orden,
            ]);

            $this->conexion->commit();
            return $detalleId;
        } catch (Throwable $exception) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $exception;
        }
    }

    public function quitarRepuesto(int $detalleId, int $orden, int $usuario): void
    {
        $this->conexion->beginTransaction();
        try {
            $ordenConsulta = $this->conexion->prepare(
                'SELECT id FROM ordenes_trabajo WHERE id = :id FOR UPDATE'
            );
            $ordenConsulta->execute(['id' => $orden]);
            if (!$ordenConsulta->fetchColumn()) {
                throw new InvalidArgumentException('Orden no encontrada.');
            }

            $detalleConsulta = $this->conexion->prepare(
                'SELECT repuesto_id, cantidad FROM repuestos_orden
                 WHERE id = :detalle AND orden_id = :orden FOR UPDATE'
            );
            $detalleConsulta->execute(['detalle' => $detalleId, 'orden' => $orden]);
            $detalle = $detalleConsulta->fetch(PDO::FETCH_ASSOC);
            if (!$detalle) {
                throw new InvalidArgumentException('El repuesto no está asociado a esta orden.');
            }

            $reponerStock = $this->conexion->prepare(
                'UPDATE repuestos SET stock = stock + :cantidad WHERE id = :id'
            );
            $reponerStock->execute([
                'cantidad' => (int) $detalle['cantidad'],
                'id' => (int) $detalle['repuesto_id'],
            ]);
            $eliminarDetalle = $this->conexion->prepare(
                'DELETE FROM repuestos_orden WHERE id = :id AND orden_id = :orden'
            );
            $eliminarDetalle->execute(['id' => $detalleId, 'orden' => $orden]);

            $movimiento = $this->conexion->prepare(
                'INSERT INTO movimientos_inventario
                    (repuesto_id, usuario_id, orden_id, tipo, cantidad, observaciones)
                 VALUES (:repuesto, :usuario, :orden, :tipo, :cantidad, :observaciones)'
            );
            $movimiento->execute([
                'repuesto' => (int) $detalle['repuesto_id'],
                'usuario' => $usuario,
                'orden' => $orden,
                'tipo' => 'entrada',
                'cantidad' => (int) $detalle['cantidad'],
                'observaciones' => 'Repuesto retirado de orden de trabajo',
            ]);

            $actualizarCosto = $this->conexion->prepare(
                'UPDATE ordenes_trabajo
                 SET costo_final = CASE
                     WHEN costo_final IS NULL THEN NULL
                     ELSE GREATEST(0, costo_final - :costo)
                 END,
                 precio_confirmado = 0
                 WHERE id = :id'
            );
            $actualizarCosto->execute([
                'costo' => (int) $detalle['cantidad'] * (float) $detalle['precio_unitario'],
                'id' => $orden,
            ]);

            $this->conexion->commit();
        } catch (Throwable $exception) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $exception;
        }
    }

    private function validarEstado(string $estado): void
    {
        if (!in_array($estado, self::ESTADOS_ORDEN, true)) {
            throw new InvalidArgumentException('El estado de la orden no es válido.');
        }
    }
}

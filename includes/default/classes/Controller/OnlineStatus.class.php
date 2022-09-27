<?php

class OnlineStatus extends BaseClass
{

    private bool
        $login_refresh,
        $enabled;

    /**
     * @fn __construct
     * @note Costruttore della classe
     */
    public function __construct()
    {
        parent::__construct();
        $this->enabled = Functions::get_constant('ONLINE_STATUS_ENABLED');
        $this->login_refresh = Functions::get_constant('ONLINE_STATUS_LOGIN_REFRESH');
    }

    /*** GETTERS */

    /**
     * @fn isEnabled
     * @note Controlla se la funzione è abilitata
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @fn refreshOnLogin
     * @note Controlla se resettare l'online status al login
     * @return bool
     */
    public function refreshOnLogin(): bool
    {
        return $this->login_refresh;
    }


    /*** CONTROLS ***/

    /**
     * @fn onlineStatusNeedRefresh
     * @note Controlla se lo status online richiede un refresh
     * @return bool
     * @throws Throwable
     */
    public function onlineStatusNeedRefresh(): bool
    {

        $pg_data = Personaggio::getPgData($this->me_id, 'online_last_refresh,ora_entrata');
        $last_refresh = Filters::out($pg_data['online_last_refresh']);
        $entrata = Filters::out($pg_data['ora_entrata']);

        return (($last_refresh < $entrata) || empty($last_refresh) || ($last_refresh == 0));
    }


    /*** PERMISSIONS ***/

    /**
     * @fn manageStatusPermission
     * @note Controlla se si hanno i permessi per la gestione degli status online
     * @return bool
     */
    public function manageStatusPermission(): bool
    {
        return Permissions::permission('MANAGE_ONLINE_STATUS');
    }


    /*** TABLES HELPERS **/

    /**
     * @fn getStatus
     * @note Estrae i dati di uno stato
     * @param int $id
     * @param string $val
     * @return DBQueryInterface
     * @throws Throwable
     */
    public function getStatus(int $id, string $val = '*'): DBQueryInterface
    {
        return DB::queryStmt("SELECT {$val} FROM online_status WHERE id=:id LIMIT 1", [
            'id' => $id,
        ]);
    }

    /**
     * @fn getStatusByType
     * @note Estrae i dati di uno stato in base al tipo
     * @param int $type
     * @param string $val
     * @return DBQueryInterface
     * @throws Throwable
     */
    public function getStatusByType(int $type, string $val = '*'): DBQueryInterface
    {
        return DB::queryStmt("SELECT {$val} FROM online_status WHERE type=:type ORDER by text", [
            'type' => $type,
        ]);
    }

    /**
     * @fn getStatusTypes
     * @note Estrae tutti i tipi di stato esistenti
     * @param string $val
     * @return DBQueryInterface
     * @throws Throwable
     */
    public function getStatusTypes(string $val = '*'): DBQueryInterface
    {
        return DB::queryStmt("SELECT * FROM online_status_type WHERE 1 ORDER BY label", []);
    }

    /**
     * @fn getStatusType
     * @note Estrae tutti i dati di uno stato specifico
     * @param int $id
     * @param string $val
     * @return DBQueryInterface
     * @throws Throwable
     */
    public function getStatusType(int $id, string $val = '*'): DBQueryInterface
    {
        return DB::queryStmt("SELECT {$val} FROM online_status_type WHERE id=:id LIMIT 1", [
            'id' => $id,
        ]);
    }

    /**
     * @fn getPgStatus
     * @note Estrae lo stato online di un pg
     * @param int $pg
     * @param int $type
     * @param string $last_refresh
     * @param string $val
     * @return DBQueryInterface
     * @throws Throwable
     */
    public function getPgStatus(int $pg, int $type, string $last_refresh = '', string $val = '*'): DBQueryInterface
    {
        $last_refresh = (!empty($last_refresh)) ? " AND last_refresh >= '{$last_refresh}' " : '';
        return DB::queryStmt("SELECT {$val} FROM personaggio_online_status WHERE personaggio=:pg AND type=:type {$last_refresh} LIMIT 1", [
            'pg' => $pg,
            'type' => $type,
        ]);
    }

    /*** AJAX ***/

    /**
     * @fn ajaxStatusData
     * @note Estrae i dati di uno stato
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function ajaxStatusData(array $post):array
    {
        if ( $this->manageStatusPermission() ) {
            $id = Filters::int($post['id']);
            $data = $this->getStatus($id);
            return [
                'type' => Filters::int($data['type']),
                'text' => Filters::out($data['text']),
            ];
        }

        return [];
    }

    /**
     * @fn ajaxStatusTypeData
     * @note Estrae i dati di un tipo di stato
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function ajaxStatusTypeData(array $post):array
    {

        if ( $this->manageStatusPermission() ) {
            $id = Filters::int($post['id']);
            $data = $this->getStatusType($id);
            return [
                'label' => Filters::out($data['label']),
                'request' => Filters::out($data['request']),
            ];
        }

        return [];
    }

    /**** LIST ****/

    /**
     * @fn renderOnlineStatusOptions
     * @note Render delle radio di scelta dello status online
     * @return string
     * @throws Throwable
     */
    public function renderOnlineStatusOptions(): string
    {

        $types = $this->getStatusTypes();

        $data = [];
        foreach ( $types as $type ) {
            $type_id = Filters::int($type['id']);
            $type_request = Filters::out($type['request']);

            $list = $this->getStatusByType($type_id);

            if ( $list->getNumRows() > 0 ) {

                $options = [];
                foreach ( $list as $row ) {
                    $id = Filters::int($row['id']);
                    $name = Filters::out($row['text']);

                    $options[] = [
                        'id' => $id,
                        'name' => $name,
                    ];
                }

                $data[] = [
                    'title' => $type_request,
                    'options' => $options,
                ];
            }
        }

        return Template::getInstance()->startTemplate()->render('OnlineStatus/options', [
            'data' => $data,
        ]);
    }

    /**
     * @fn listStatusType
     * @note Crea la lista select dei tipi
     * @param int $selected
     * @return string
     * @throws Throwable
     */
    public function listStatusType(int $selected = 0): string
    {
        $types = $this->getStatusTypes();
        return Template::getInstance()->startTemplate()->renderSelect('id','label',$selected,$types);
    }

    /**
     * @fn listStatus
     * @note Render della lista degli stati disponibili, divisi per tipo
     * @return string
     * @throws Throwable
     * // TODO Richiede un template per il raggruppamento con optgroup delle select
     */
    public function listStatus(): string
    {

        $html = '<option value=""></option>';
        $types = $this->getStatusTypes();

        foreach ( $types as $type ) {
            $type_id = Filters::int($type['id']);
            $type_label = Filters::out($type['label']);

            $list = $this->getStatusByType($type_id);

            $html .= "<optgroup label='{$type_label}'>";

            foreach ( $list as $row ) {
                $id = Filters::int($row['id']);
                $name = Filters::out($row['text']);

                $html .= "<option value='{$id}'>{$name}</option>";
            }

            $html .= "</optgroup>";

        }

        return $html;

    }

    /**
     * @fn renderStatusOnline
     * @note Visualizza le risposte nello status online
     * @param int $pg
     * @return string
     * @throws Throwable
     * TODO Collegare nel punto dove far comparire lo status online
     */
    public function renderStatusOnline(int $pg): string
    {
        $html = '';
        $pg = Filters::int($pg);
        $pg_data = Personaggio::getPgData($pg, 'ora_entrata');
        $last_login = ($this->refreshOnLogin()) ? Filters::out($pg_data['ora_entrata']) : '';
        $types = $this->getStatusTypes();

        foreach ( $types as $type ) {
            $type_id = Filters::int($type['id']);
            $type_label = Filters::out($type['label']);

            $list = $this->getPgStatus($pg, $type_id, $last_login, 'value');
            if ( !empty($list['value']) ) {
                $status_data = $this->getStatus(Filters::int($list['value']), 'text');
                $status_text = Filters::out($status_data['text']);
            } else {
                $status_text = 'Non definito';
            }

            $html .= " <span>{$type_label} : {$status_text}</span> | ";
        }

        return $html;
    }

    /*** FUNCTIONS ***/

    /**
     * @fn setOnlineStatus
     * @note Update dello status online
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function setOnlineStatus(array $post): array
    {
        if ( $this->isEnabled() ) {

            $online_status = $post['online_status'];

            foreach ( $online_status as $status_id => $value ) {

                $value = Filters::int($value);
                $split = explode('_', $status_id);
                $type = Filters::int($split[1]);

                $contr = $this->getPgStatus($this->me_id, $type);

                if ( isset($contr['id']) ) {
                    DB::queryStmt("UPDATE personaggio_online_status SET value=:value,last_refresh=NOW() WHERE personaggio=:pg AND type=:type LIMIT 1",[
                        'value' => $value,
                        'pg' => $this->me_id,
                        'type' => $type,
                    ]);
                } else {
                    DB::queryStmt("INSERT INTO personaggio_online_status (personaggio,type,value) VALUES (:pg,:type,:value)",[
                        'pg' => $this->me_id,
                        'type' => $type,
                        'value' => $value,
                    ]);
                }

            }

            DB::queryStmt("UPDATE personaggio SET online_last_refresh=NOW() WHERE id=:pg LIMIT 1",[
                'pg' => $this->me_id,
            ]);

            return [
                'response' => true,
                'swal_title' => 'Operazione riuscita!',
                'swal_message' => 'Status online settato correttamente.',
                'swal_type' => 'success',
            ];
        } else {
            return [
                'response' => false,
                'swal_title' => 'Operazione fallita!',
                'swal_message' => 'Errore, funzione non abilitata.',
                'swal_type' => 'error',
            ];
        }

    }


    /*** MANAGEMENT FUNCTIONS **/

    /**
     * @fn insertStatus
     * @note Funzione d'inserimento di un nuovo stato
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function insertStatus(array $post): array
    {

        if ( $this->manageStatusPermission() ) {

            $type = Filters::int($post['type']);
            $text = Filters::in($post['text']);

            DB::queryStmt("INSERT INTO online_status (type,text) VALUES (:type,:text)",[
                'type' => $type,
                'text' => $text,
            ]);

            return [
                'response' => true,
                'swal_title' => 'Operazione riuscita!',
                'swal_message' => 'Stato inserito correttamente.',
                'swal_type' => 'success',
                'status_list' => $this->listStatus(),
            ];

        } else {
            return [
                'response' => false,
                'swal_title' => 'Operazione fallita!',
                'swal_message' => 'Permesso negato.',
                'swal_type' => 'error',
            ];
        }

    }

    /**
     * @fn editStatus
     * @note Funzione di modifica di uno stato
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function editStatus(array $post): array
    {

        if ( $this->manageStatusPermission() ) {

            $id = Filters::int($post['id']);
            $type = Filters::int($post['type']);
            $text = Filters::in($post['text']);

            DB::queryStmt("UPDATE online_status SET type=:type,text=:text WHERE id=:id LIMIT 1",[
                'id' => $id,
                'type' => $type,
                'text' => $text,
            ]);

            return [
                'response' => true,
                'swal_title' => 'Operazione riuscita!',
                'swal_message' => 'Stato modificato correttamente.',
                'swal_type' => 'success',
                'status_list' => $this->listStatus(),
            ];

        } else {
            return [
                'response' => false,
                'swal_title' => 'Operazione fallita!',
                'swal_message' => 'Permesso negato.',
                'swal_type' => 'error',
            ];
        }
    }

    /**
     * @fn editStatus
     * @note Funzione di eliminazione di uno stato
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function deleteStatus(array $post): array
    {

        if ( $this->manageStatusPermission() ) {

            $id = Filters::int($post['id']);

            DB::queryStmt("DELETE FROM online_status WHERE id=:id LIMIT 1",[
                'id' => $id,
            ]);

            return [
                'response' => true,
                'swal_title' => 'Operazione riuscita!',
                'swal_message' => 'Stato eliminato correttamente.',
                'swal_type' => 'success',
                'status_list' => $this->listStatus(),
            ];

        } else {
            return [
                'response' => false,
                'swal_title' => 'Operazione fallita!',
                'swal_message' => 'Permesso negato.',
                'swal_type' => 'error',
            ];
        }
    }

    /**
     * @fn insertStatusType
     * @note Funzione d'inserimento di un nuovo tipo di stato
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function insertStatusType(array $post): array
    {

        if ( $this->manageStatusPermission() ) {

            $label = Filters::in($post['label']);
            $request = Filters::in($post['request']);

            DB::queryStmt("INSERT INTO online_status_type (label,request) VALUES (:label,:request)",[
                'label' => $label,
                'request' => $request,
            ]);

            return [
                'response' => true,
                'swal_title' => 'Operazione riuscita!',
                'swal_message' => 'Tipo di stato inserito correttamente.',
                'swal_type' => 'success',
                'status_list' => $this->listStatusType(),
            ];
        } else {
            return [
                'response' => false,
                'swal_title' => 'Operazione fallita!',
                'swal_message' => 'Permesso negato.',
                'swal_type' => 'error',
            ];
        }
    }

    /**
     * @fn editStatusType
     * @note Funzione di modifica di un tipo di stato
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function editStatusType(array $post): array
    {

        if ( $this->manageStatusPermission() ) {

            $id = Filters::int($post['id']);
            $label = Filters::in($post['label']);
            $request = Filters::in($post['request']);

            DB::queryStmt("UPDATE online_status_type SET label=:label,request=:request WHERE id=:id LIMIT 1",[
                'id' => $id,
                'label' => $label,
                'request' => $request,
            ]);

            return [
                'response' => true,
                'swal_title' => 'Operazione riuscita!',
                'swal_message' => 'Tipo di stato modificato correttamente.',
                'swal_type' => 'success',
                'status_list' => $this->listStatusType(),
            ];
        } else {
            return [
                'response' => false,
                'swal_title' => 'Operazione fallita!',
                'swal_message' => 'Permesso negato.',
                'swal_type' => 'error',
            ];
        }
    }

    /**
     * @fn deleteStatusType
     * @note Funzione di eliminazione di un tipo di stato
     * @param array $post
     * @return array
     * @throws Throwable
     */
    public function deleteStatusType(array $post): array
    {

        if ( $this->manageStatusPermission() ) {

            $id = Filters::int($post['id']);

            DB::queryStmt("DELETE FROM online_status_type WHERE id=:id LIMIT 1",[
                'id' => $id,
            ]);
            DB::queryStmt("DELETE FROM online_status WHERE type=:id",[
                'id' => $id,
            ]);

            return [
                'response' => true,
                'swal_title' => 'Operazione riuscita!',
                'swal_message' => 'Tipo di stato eliminato correttamente.',
                'swal_type' => 'success',
                'status_list' => $this->listStatusType(),
            ];

        } else {
            return [
                'response' => false,
                'swal_title' => 'Operazione fallita!',
                'swal_message' => 'Permesso negato.',
                'swal_type' => 'error',
            ];
        }
    }
}
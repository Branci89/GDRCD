<?php

/**
 * @class GatheringItem
 * @note Classe per la gestione degli oggetti trovabili
 * @required PHP 7.1+
 */
class GatheringItem extends Gathering
{
    /**** ROUTING ***/

    /**
     * @fn loadManagementGatheringItemPage
     * @note Routing delle pagine di gestione
     * @param string $op
     * @return string
     */
    public function loadManagementGatheringItemPage(string $op): string
    {
        $op = Filters::out($op);

        switch ($op) {
            default:
                $page = 'gathering_item_list.php';
                break;

            case 'new_item':
                $page = 'gathering_item_new.php';
                break;

            case 'edit_item':
                $page = 'gathering_item_edit.php';
                break;

            case 'delete_item':
                $page = 'gathering_item_delete.php';
                break;

        }

        return $page;
    }

    /*** TABLES HELPERS ***/

    /**
     * @fn getAllGatheringItem
     * @note Ottiene la lista degli esiti
     * @param string $val
     * @param string $order
     * @return bool|int|mixed|string
     */
    public function getAllGatheringItem(string $val = '*', string $order = '')
    {
        $where = ($this->gatheringManage()) ? '1' : "(master = '0' OR master = '{$this->me_id}')";

        return DB::query("SELECT {$val} FROM gathering_item WHERE {$where} {$order}", 'result');
    }
    /*** GATHERING INDEX ***/

    /**
     * @fn GatheringCatList
     * @note Render html della lista delle categorie
     * @return string
     */
    public function GatheringItemList()
    {
        $list = $this->getAllGatheringItem( '*', 'ORDER BY nome ASC');
        return $this->renderGatheringItemList($list);
    }

    /**
     * @fn renderGatheringItemList
     * @note Render html lista oggetti
     * @param object $list
     * @param string $page
     * @return string
     */
    public function renderGatheringItemList(object $list)
    {
        $html = '<div class="tr header">
                    <div class="td">Nome</div>
                    <div class="td">Categoria</div>      
                    <div class="td">Immagine</div>    
                    <div class="td">Creato da</div>  
                    <div class="td">Creato il</div>';
        if ($this->gatheringRarity()) {
            $html .= '<div class="td">Quantità</div>';
        }
        $html.='    <div class="td">Controlli</div>
                </div>';

        foreach ($list as $row) {
            $id = Filters::int($row['id']);
            $nome = Filters::in($row['nome']);
            $categoria = $this->getoneGatheringCat(Filters::int($row['categoria']));
            $immagine= Filters::in($row['immagine']);
            $creato_da=Filters::in($row['creato_da']);
            $creato_il=Filters::date($row['creato_il'],"d/m/Y");
            $quantita=Filters::in($row['quantita']);


            $html .= "<div class='tr'>";
            $html .= "<div class='td'>" . $nome. '</div>';
            $html .= "<div class='td'>" . $categoria['nome']. '</div>';
            $html .= "<div class='td'><img src='" . $immagine. "'></div>";
            $html .= "<div class='td'>" . $creato_da. '</div>';
            $html .= "<div class='td'>" . $creato_il. '</div>';
            if ($this->gatheringRarity()) {
                $html .= "<div class='td'>" . $quantita. '</div>';
            }

            $html .= "<div class='td commands'>";

            if ($this->gatheringManage()) {
                $html .= "<a href='/main.php?page=gestione_gathering_item&op=edit_item&id={$id}' title='Modifica'><i class='fas fa-edit'></i></a>";
            }

            if ($this->gatheringManage()) {
                $html .= " <a class='ajax_link' data-id='{$id}' data-action='delete_item' href='#' title='Elimina'><i class='far fa-trash'></i></a>";
            }


            $html .= "</div>";
            $html .= "</div>";
        }

        $html .= "<div class='tr footer'>";

        $html .= "<a href = 'main.php?page=gestione_gathering_item&op=new_item' >
                            Nuovo Oggetto
                        </a > |
                    <a href = '/main.php?page=gestione' > Indietro</a >";


        $html .= "</div > ";

        return $html;
    }
    /*** NEW ITEM ***/

    /**
     * @fn newGatheringItem
     * @note Inserisce una nuova categoria
     * @param array $post
     * @return array
     */
    public function newGatheringItem(array $post): array
    {

        if ($this->gatheringManage()) {

            $nome = Filters::in($post['nome']);
            $descrizione = Filters::in($post['descrizione']);
            $immagine= Filters::in($post['immagine']);
            $quantita=Filters::in($post['quantita']);
            $creato_il= date("d-m-Y");
            $categoria=Filters::in($post['categoria']);
            if ($this->gatheringRarity()) {
                $quantita=Filters::in($post['quantita']);
                DB::query("INSERT INTO gathering_item(nome, descrizione,categoria,immagine, creato_da, creato_il, quantita) VALUES('{$nome}', '{$descrizione}', {$categoria},'{$immagine}', '{$this->me}', '{$creato_il}', {$quantita})");

            }else{
                DB::query("INSERT INTO gathering_item(nome, descrizione,categoria,immagine, creato_da, creato_il, quantita) VALUES('{$nome}', '{$descrizione}', {$categoria},'{$immagine}', '{$this->me}', '{$creato_il}', 0)");

            }


            return [
                'response' => true,
                'swal_title' => 'Operazione riuscita!',
                'swal_message' => 'Oggetto creato con successo.',
                'swal_type' => 'success'
            ];
        } else {
            return [
                'response' => false,
                'swal_title' => 'Operazione fallita!',
                'swal_message' => 'Permesso negato.',
                'swal_type' => 'error'
            ];
        }
    }
}

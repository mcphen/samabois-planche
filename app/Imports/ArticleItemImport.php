<?php

namespace App\Imports;

use App\Models\ArticleItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ArticleItemImport implements ToModel, WithHeadingRow
{
    protected $articleId;

    public function __construct($articleId)
    {
        $this->articleId = $articleId;
    }

    public function model(array $row)
    {
        return ArticleItem::firstOrCreate([
            'article_id'    => $this->articleId,
            'numero_colis'  => $row['numero_colis'],
        ],[

            'longueur'      => $row['longueur'],
            'largeur'       => $row['largeur'],
            'epaisseur'     => $row['epaisseur'],
            'nombre_piece'  => $row['nombre_piece'],
            'volume'        => $row['volume'],
        ]);
    }
}

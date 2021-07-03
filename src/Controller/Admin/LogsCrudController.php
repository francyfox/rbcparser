<?php

namespace App\Controller\Admin;

use App\Entity\Logs;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class LogsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Logs::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}

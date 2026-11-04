<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\Auditoria;

class AuditoriaController extends Controller {
  public function index(): void {
    $this->auth();
    $this->view('auditoria', ['titulo' => 'Auditoria', 'logs' => Auditoria::recentes()]);
  }
}

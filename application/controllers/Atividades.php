<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Atividades extends REST_Controller
{
	function __construct()
	{
		header("Access-Control-Allow-Origin: *");
		header('Access-Control-Allow-Headers: X-Requested-With, content-type, X-Token, x-token');
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
		$method = $_SERVER['REQUEST_METHOD'];
		if ($method == "OPTIONS") {
			die();
		}
		parent::__construct();
		header('Content-Type: application/json');
	}


	public function index_get()
	{
		echo "Metodo ou requisição incorreta";
	}

	// retorna todos os projetos com suas respectivas atividades
	public function projetos_get()
	{

		$projetosData = [];
		$projetos = $this->doctrine->em->getRepository("Entity\Projeto")->findBy(array(), array("id" => "asc"));
		foreach ($projetos as $projeto) {
			$atividadeData = [];
			$atividades = $this->doctrine->em->getRepository("Entity\Atividade")->findBy(array("idProjeto" => $projeto->getId()), array("dataCadastro" => "asc"));
			foreach ($atividades as $atividade) {
				$atividadeData[] = [
					"id" => $atividade->getId(),
					"data" => $atividade->getDataCadastro(),
					"descricao" => $atividade->getDescricao(),
					"idProjeto" => $atividade->getIdProjeto()
				];
			}
			$projetosData[] = [
				"id" => $projeto->getId(),
				"descricao" => $projeto->getDescricao(),
				"atividades" => $atividadeData
			];
		}

		$this->response($projetosData, REST_Controller::HTTP_OK);
	}

	public function filtrarProjeto_get($idProjeto)
	{

		$data = [];
		$projetos = $this->doctrine->em->getRepository("Entity\Projeto")->findBy(array("id" => $idProjeto), array("id" => "asc"));
		foreach ($projetos as $projeto) {
			$data[] = [
				"descricao" => $projeto->getDescricao(),
			];
		}

		$this->response($data, REST_Controller::HTTP_OK);
	}

	// busca atividade pelo ID
	public function filtrarAtividade_get($idAtividade)
	{
		$data = [];
		$atividades = $this->doctrine->em->getRepository("Entity\Atividade")->findBy(array("id" => $idAtividade), array("id" => "asc"));
		foreach ($atividades as $atividade) {
			$data[] = [
				"descricao" => $atividade->getDescricao(),
			];
		}

		$this->response($data, REST_Controller::HTTP_OK);
	}

	// Metodo para criação de projetos
	public function criarProjeto_post()
	{
		// Request.body com os dados que vem do react
		$projetoData = json_decode($this->input->raw_input_stream, true);

		if (!$projetoData) {
			echo json_encode(["message" => "É preciso informar os dados para criar um projeto"]);
			exit;
		}

		$projeto = new Entity\Projeto;
		$projeto->setDescricao($projetoData['descricao']);
		$this->doctrine->em->persist($projeto);
		$this->doctrine->em->flush();

		$this->response(["message" => "Projeto criado"], REST_Controller::HTTP_OK);
	}

	// Metodo para criação de atividades
	public function criarAtividade_post()
	{
		// Request.body com os dados que vem do react
		$atividadeData = json_decode($this->input->raw_input_stream, true);

		if (!$atividadeData) {
			echo json_encode(["message" => "É preciso informar os dados para criar uma atividade"]);
			exit;
		}

		$atividade = new Entity\Atividade;
		$atividade->setDescricao($atividadeData['descricao']);
		$atividade->setIdProjeto($atividadeData['idProjeto']);
		$atividade->setDataCadastro(date("Y-m-d H:i:s"));
		$this->doctrine->em->persist($atividade);
		$this->doctrine->em->flush();

		$this->response(["message" => "Atividade criada"], REST_Controller::HTTP_OK);
	}

	// Metodo para alterar informações de um projeto
	public function editarProjeto_put($idProjeto)
	{

		$projetoData = json_decode($this->input->raw_input_stream, true);

		if (!$projetoData) {
			exit;
		}

		$projeto = $this->doctrine->em->find("Entity\Projeto", $idProjeto);
		// verifica a existencia do projeto
		if (!$projeto) {
			echo json_encode(["message" => "Não é possivel editar um projeto inexistente"]);
			exit;
		}
		$projeto->setDescricao($projetoData['descricao']);

		$this->doctrine->em->merge($projeto);
		$this->doctrine->em->flush();

		$this->response($projeto, REST_Controller::HTTP_OK);
	}

	// Metodo para alterar informações de uma atividade
	public function editarAtividade_put($idAtividade)
	{
		$atividadeData = json_decode($this->input->raw_input_stream, true);

		if (!$atividadeData) {
			exit;
		}

		$atividade = $this->doctrine->em->find("Entity\Atividade", $idAtividade);

		// verifica a existencia da atividade
		if (!$atividade) {
			echo json_encode(["message" => "Não é possivel editar uma atividade inexistente"]);
			exit;
		}
		$atividade->setDescricao($atividadeData['descricao']);

		$this->doctrine->em->merge($atividade);
		$this->doctrine->em->flush();

		$this->response($atividade, REST_Controller::HTTP_OK);
	}

	// Metodo para deletar projeto e atividades presentes nele
	public function deletarProjeto_delete($idProjeto)
	{
		// encontra o projeto de acordo com o ID
		$projeto = $this->doctrine->em->find("Entity\Projeto", $idProjeto);

		// verifica se o projeto existe, caso nao exista retorna erro
		if (!$projeto) {
			echo json_encode(["message" => "Não é possivel deletar um projeto inexistente"]);
			exit;
		}

		// retorna as atividades cadastradas neste projeto
		$atividades = $this->doctrine->em->getRepository("Entity\Atividade")
			->findBy(array("idProjeto" => $idProjeto));

		// Deleta primeiro as atividades deste projeto
		foreach ($atividades as $atividade) {
			$this->doctrine->em->remove($atividade);
			$this->doctrine->em->flush();
		}

		// em seguida deleta o projeto
		$this->doctrine->em->remove($projeto);
		$this->doctrine->em->flush();

		$this->response(["message" => "Projeto deletado"], REST_Controller::HTTP_OK);
	}

	// Metodo para deletar atividades de um projeto
	public function deletarAtividade_delete($idAtividade)
	{
		// encontra a atividade de acordo com o ID
		$atividade = $this->doctrine->em->find("Entity\Atividade", $idAtividade);

		// verifica se a atividade existe, caso nao exista retorna erro
		if (!$atividade) {
			echo json_encode(["message" => "Não é possivel deletar uma atividade inexistente"]);
			exit;
		}

		// deleta a atividade
		$this->doctrine->em->remove($atividade);
		$this->doctrine->em->flush();

		$this->response(["message" => "Atividade deletada"], REST_Controller::HTTP_OK);
	}
}

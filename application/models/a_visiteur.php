<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class A_visiteur extends CI_Model {

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();

		// chargement du modèle d'accès aux données qui est utile à toutes les méthodes
		$this->load->model('dataaccess');
    }

	/**
	 * Accueil du visiteur
	 * La fonction intègre un mécanisme de contrôle d'existence des
	 * fiches de frais sur les 6 derniers mois.
	 * Si l'une d'elle est absente, elle est créée
	*/
	public function accueil()
	{	// TODO : Contrôler que toutes les valeurs de $unMois sont valides (chaine de caractère dans la BdD)

		// chargement du modèle contenant les fonctions génériques
		$this->load->model('functionslib');

		// obtention de la liste des 6 derniers mois (y compris celui ci)
		$lesMois = $this->functionslib->getSixDerniersMois();

		// obtention de l'id de l'utilisateur mémorisé en session
		$idVisiteur = $this->session->userdata('idUser');

		// contrôle de l'existence des 6 dernières fiches et création si nécessaire
		foreach ($lesMois as $unMois){
			if(!$this->dataaccess->ExisteFiche($idVisiteur, $unMois)) $this->dataaccess->creeFiche($idVisiteur, $unMois);
		}
		// envoie de la vue accueil du visiteur
    if($this->session->userdata('etat') == "visiteur"){
      $data['type'] = "visiteur";
    }
    else{
      $data['type'] = "comptable";
    }

		$this->templates->load('t_visiteur', 'v_visAccueil', $data);
	}

	/**
	 * Liste les fiches existantes du visiteur connecté et
	 * donne accès aux fonctionnalités associées
	 *
	 * @param $idVisiteur : l'id du visiteur
	 * @param $message : message facultatif destiné à notifier l'utilisateur du résultat d'une action précédemment exécutée
	*/
	public function mesFiches ($idVisiteur, $message=null)
	{	// TODO : s'assurer que les paramètres reçus sont cohérents avec ceux mémorisés en session

		$idVisiteur = $this->session->userdata('idUser');

		$data['notify'] = $message;
		$data['mesFiches'] = $this->dataaccess->getFiches($idVisiteur);
    $data['lesCoutsForfait'] = $this->dataaccess->getLesFraisForfait();
		$this->templates->load('t_visiteur', 'v_visMesFiches', $data);
	}

	/**
	 * Présente le détail de la fiche sélectionnée
	 *
	 * @param $idVisiteur : l'id du visiteur
	 * @param $mois : le mois de la fiche à modifier
	*/
	public function voirFiche($idVisiteur, $mois)
	{	// TODO : s'assurer que les paramètres reçus sont cohérents avec ceux mémorisés en session

		$data['numAnnee'] = substr( $mois,0,4);
		$data['numMois'] = substr( $mois,4,2);
		$data['lesFraisHorsForfait'] = $this->dataaccess->getLesLignesHorsForfait($idVisiteur,$mois);
		$data['lesFraisForfait'] = $this->dataaccess->getLesLignesForfait($idVisiteur,$mois);

		$this->templates->load('t_visiteur', 'v_visVoirListeFrais', $data);
	}

	/**
	 * Présente le détail de la fiche sélectionnée et donne
	 * accés à la modification du contenu de cette fiche.
	 *
	 * @param $idVisiteur : l'id du visiteur
	 * @param $mois : le mois de la fiche à modifier
	 * @param $message : message facultatif destiné à notifier l'utilisateur du résultat d'une action précédemment exécutée
	*/
	public function modFiche($idVisiteur, $mois, $message=null)
	{	// TODO : s'assurer que les paramètres reçus sont cohérents avec ceux mémorisés en session

		$data['notify'] = $message;
		$data['numAnnee'] = substr( $mois,0,4);
		$data['numMois'] = substr( $mois,4,2);
		$data['lesFraisHorsForfait'] = $this->dataaccess->getLesLignesHorsForfait($idVisiteur,$mois);
		$data['lesFraisForfait'] = $this->dataaccess->getLesLignesForfait($idVisiteur,$mois);
    $data['lesCoutsForfait'] = $this->dataaccess->getLesFraisForfait();

		$this->templates->load('t_visiteur', 'v_visModListeFrais', $data);
	}

	/**
	 * Signe une fiche de frais en changeant son état
	 *
	 * @param $idVisiteur : l'id du visiteur
	 * @param $mois : le mois de la fiche à signer
	*/
	public function signeFiche($idVisiteur, $mois)
	{	// TODO : s'assurer que les paramètres reçus sont cohérents avec ceux mémorisés en session
		// TODO : intégrer une fonctionnalité d'impression PDF de la fiche

	    $this->dataaccess->signeFiche($idVisiteur, $mois);
	}

	/**
	 * Modifie les quantités associées aux frais forfaitisés dans une fiche donnée
	 *
	 * @param $idVisiteur : l'id du visiteur
	 * @param $mois : le mois de la fiche concernée
	 * @param $lesFrais : les quantités liées à chaque type de frais, sous la forme d'un tableau
	*/
	public function majForfait($idVisiteur, $mois, $lesFrais, $MontantFrais)
	{	// TODO : s'assurer que les paramètres reçus sont cohérents avec ceux mémorisés en session
		// TODO : valider les données contenues dans $lesFrais ...

		$this->dataaccess->majLignesForfait($idVisiteur,$mois,$lesFrais, $MontantFrais);
		$this->dataaccess->recalculeMontantFiche($idVisiteur,$mois);
	}

	/**
	 * Ajoute une ligne de frais hors forfait dans une fiche donnée
	 *
	 * @param $idVisiteur : l'id du visiteur
	 * @param $mois : le mois de la fiche concernée
	 * @param $lesFrais : les quantités liées à chaque type de frais, sous la forme d'un tableau
	*/
	public function ajouteFrais($idVisiteur, $mois, $uneLigne)
	{	// TODO : s'assurer que les paramètres reçus sont cohérents avec ceux mémorisés en session
		// TODO : valider la donnée contenues dans $uneLigne ...

		$dateFrais = $uneLigne['dateFrais'];
		$libelle = $uneLigne['libelle'];
		$montant = $uneLigne['montant'];

		$this->dataaccess->creeLigneHorsForfait($idVisiteur,$mois,$libelle,$dateFrais,$montant);
	}

	/**
	 * Supprime une ligne de frais hors forfait dans une fiche donnée
	 *
	 * @param $idVisiteur : l'id du visiteur
	 * @param $mois : le mois de la fiche concernée
	 * @param $idLigneFrais : l'id de la ligne à supprimer
	*/
	public function supprLigneFrais($idVisiteur, $mois, $idLigneFrais)
	{	// TODO : s'assurer que les paramètres reçus sont cohérents avec ceux mémorisés en session et cohérents entre eux

	    $this->dataaccess->supprimerLigneHorsForfait($idLigneFrais);
	}

  public function impressionFrais($idVisiteur,$mois, $message=null){

    $data['notify'] = $message;
    $data['numAnnee'] = substr( $mois,0,4);
    $data['numMois'] = substr( $mois,4,2);
    $data['lesFraisHorsForfait'] = $this->dataaccess->getLesLignesHorsForfait($idVisiteur,$mois);
    $data['lesFraisForfait'] = $this->dataaccess->getLesLignesForfait($idVisiteur,$mois);
    $data['lesCoutsForfait'] = $this->dataaccess->getLesFraisForfait();

    $this->templates->load('t_visiteur', 'v_visImpressiondFicheFrais', $data);
  }
}

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Modèle qui implémente les fonctions d'accès aux données
*/
class dataaccess extends CI_Model {
  // TODO : Transformer toutes les requêtes en requêtes paramétrées

  function __construct()
  {
    // Call the Model constructor
    parent::__construct();
  }

  /**
  * Retourne les informations d'un visiteur
  *
  * @param $login
  * @param $mdp
  * @return l'id, le nom et le prénom sous la forme d'un tableau associatif
  */
  public function getInfosVisiteur($login, $mdp){
    $req = "select visiteur.id as id, visiteur.nom as nom, visiteur.prenom as prenom, visiteur.etat as etat
    from visiteur
    where visiteur.login=? and visiteur.mdp=?";
    $rs = $this->db->query($req, array ($login, $mdp));
    $ligne = $rs->first_row('array');
    return $ligne;
  }

  /**
  * Retourne sous forme d'un tableau associatif toutes les lignes de frais hors forfait
  * concernées par les deux arguments
  * La boucle foreach ne peut être utilisée ici car on procède
  * à une modification de la structure itérée - transformation du champ date-
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  * @return tous les champs des lignes de frais hors forfait sous la forme d'un tableau associatif
  */
  public function getLesLignesHorsForfait($idVisiteur,$mois){
    $this->load->model('functionslib');

    $req = "select *
    from lignefraishorsforfait
    where lignefraishorsforfait.idvisiteur ='$idVisiteur'
    and lignefraishorsforfait.mois = '$mois' ";
    $rs = $this->db->query($req);
    $lesLignes = $rs->result_array();
    $nbLignes = $rs->num_rows();
    for ($i=0; $i<$nbLignes; $i++){
      $date = $lesLignes[$i]['date'];
      $lesLignes[$i]['date'] =  $this->functionslib->dateAnglaisVersFrancais($date);
    }
    return $lesLignes;
  }

  /**
  * Retourne le nombre de justificatif d'un visiteur pour un mois donné
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  * @return le nombre entier de justificatifs
  */
  public function getNbjustificatifs($idVisiteur, $mois){
    $req = "select fichefrais.nbjustificatifs as nb
    from  fichefrais
    where fichefrais.idvisiteur ='$idVisiteur' and fichefrais.mois = '$mois'";
    $rs = $this->db->query($req);
    $laLigne = $rs->result_array();
    return $laLigne['nb'];
  }

  /**
  * Retourne sous forme d'un tableau associatif toutes les lignes de frais au forfait
  * concernées par les deux arguments
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  * @return l'id, le libelle et la quantité sous la forme d'un tableau associatif
  */
  public function getLesLignesForfait($idVisiteur, $mois){
    $req = "select fraisforfait.id as idfrais, fraisforfait.libelle as libelle,fraisforfait.montant as montantFrais, lignefraisforfait.quantite as quantite
    from lignefraisforfait inner join fraisforfait
    on fraisforfait.id = lignefraisforfait.idfraisforfait
    where lignefraisforfait.idvisiteur ='$idVisiteur' and lignefraisforfait.mois='$mois'
    order by lignefraisforfait.idfraisforfait";
    $rs = $this->db->query($req);
    $lesLignes = $rs->result_array();
    return $lesLignes;
  }

  /**
  * Retourne tous les FraisForfait
  *
  * @return un tableau associatif contenant les fraisForfaits
  */
  public function getLesFraisForfait(){
    $req = "select fraisforfait.id as idfrais, libelle, montant from fraisforfait order by fraisforfait.id";
    $rs = $this->db->query($req);
    $lesLignes = $rs->result_array();
    return $lesLignes;
  }

  /**
  * Met à jour la table ligneFraisForfait pour un visiteur et
  * un mois donné en enregistrant les nouveaux montants
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  * @param $lesFrais tableau associatif de clé idFrais et de valeur la quantité pour ce frais
  */
  public function majLignesForfait($idVisiteur, $mois, $lesFrais){
    $lesCles = array_keys($lesFrais);
    foreach($lesCles as $unIdFrais){
      $qte = $lesFrais[$unIdFrais];
      $req = "update lignefraisforfait
      set lignefraisforfait.quantite = $qte
      where lignefraisforfait.idvisiteur = '$idVisiteur'
      and lignefraisforfait.mois = '$mois'
      and lignefraisforfait.idfraisforfait = '$unIdFrais'";
      $this->db->simple_query($req);
    }
  }

  public function validerFicheVisi($idVisiteur, $mois){
    $dateActuelle = date("Y-m-d");

    $data = array('idEtat' => 'VA', 'dateModif' => $dateActuelle);

    $where = array('idVisiteur' => $idVisiteur, 'mois' => $mois) ;

    $this->db->update('fichefrais', $data, $where);
  }

  public function refuserFicheVisi($idVisiteur, $mois){
    $dateActuelle = date("Y-m-d");

    $data = array('idEtat' => 'RF', 'dateModif' => $dateActuelle, 'commentaire' => 'Votre fiche a été refusée');

    $where = array('idVisiteur' => $idVisiteur, 'mois' => $mois) ;

    $this->db->update('fichefrais', $data, $where);
  }

  /**
  * met à jour le nombre de justificatifs de la table ficheFrais
  * pour le mois et le visiteur concerné
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  */
  public function majNbJustificatifs($idVisiteur, $mois, $nbJustificatifs){
    $req = "update fichefrais
    set nbjustificatifs = $nbJustificatifs
    where fichefrais.idvisiteur = '$idVisiteur'
    and fichefrais.mois = '$mois'";
    $this->db->simple_query($req);
  }

  /**
  * Teste si un visiteur possède une fiche de frais pour le mois passé en argument
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  * @return vrai si la fiche existe, ou faux sinon
  */
  public function existeFiche($idVisiteur,$mois)
  {
    $ok = false;
    $req = "select count(*) as nblignesfrais
    from fichefrais
    where fichefrais.mois = '$mois' and fichefrais.idvisiteur = '$idVisiteur'";
    $rs = $this->db->query($req);
    $laLigne = $rs->first_row('array');
    if($laLigne['nblignesfrais'] != 0){
      $ok = true;
    }
    return $ok;
  }

  /**
  * Crée une nouvelle fiche de frais et les lignes de frais au forfait pour un visiteur et un mois donnés
  * L'état de la fiche est mis à 'CR'
  * Lles lignes de frais forfait sont affectées de quantités nulles et du montant actuel de FraisForfait
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  */
  public function creeFiche($idVisiteur,$mois){
    $req = "insert into fichefrais(idvisiteur,mois,nbJustificatifs,montantValide,dateModif,idEtat)
    values('$idVisiteur','$mois',0,0,now(),'CR')";
    $this->db->simple_query($req);
    $lesFF = $this->getLesFraisForfait();
    foreach($lesFF as $uneLigneFF){
      $unIdFrais = $uneLigneFF['idfrais'];
      $montantU = $uneLigneFF['montant'];
      $req = "insert into lignefraisforfait(idvisiteur,mois,idFraisForfait,quantite, montantApplique)
      values('$idVisiteur','$mois','$unIdFrais',0, $montantU)";
      $this->db->simple_query($req);
    }
  }

  /**
  * Signe une fiche de frais en modifiant son état de "CR" à "CL"
  * Ne fait rien si l'état initial n'est pas "CR"
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  */
  public function signeFiche($idVisiteur,$mois){
    //met à 'CL' son champs idEtat
    $DateAujourdhui = date("Y-m");
    $Mois = $this->DecompositionMois($mois);
    $Annee = $this->DecompositionAnnee($mois);

    $datedeFiche1 = date_create(($Annee)."-".$Mois);
    $datedeFiche2 = date_create(($Annee+1)."-".$Mois);

    $laFiche = $this->getLesInfosFicheFrais($idVisiteur,$mois);

    //echo $DateAujourdhui;
    if($laFiche['idEtat']=='CR'){
      if($DateAujourdhui >= date_format($datedeFiche1,"Y-m") && $DateAujourdhui <= date_format($datedeFiche2,"Y-m")){
        $this->majEtatFicheFrais($idVisiteur, $mois,'CL');
      }else {
        $this->majEtatFicheFrais($idVisiteur, $mois, 'EX');
      }
    }
  }

  /**
  * Crée un nouveau frais hors forfait pour un visiteur un mois donné
  * à partir des informations fournies en paramètre
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  * @param $libelle : le libelle du frais
  * @param $date : la date du frais au format français jj//mm/aaaa
  * @param $montant : le montant
  */
  public function creeLigneHorsForfait($idVisiteur,$mois,$libelle,$date,$montant){
    $this->load->model('functionslib');

    $dateFr = $this->functionslib->dateFrancaisVersAnglais($date);
    $req = "insert into lignefraishorsforfait
    values('','$idVisiteur','$mois','$libelle','$dateFr','$montant')";
    $this->db->simple_query($req);
  }

  /**
  * Supprime le frais hors forfait dont l'id est passé en argument
  *
  * @param $idFrais
  */
  public function supprimerLigneHorsForfait($idFrais){
    $req = "delete from lignefraishorsforfait
    where lignefraishorsforfait.id =$idFrais ";
    $this->db->simple_query($req);
  }

  /**
  * Retourne les mois pour lesquel un visiteur a une fiche de frais
  *
  * @param $idVisiteur
  * @return un tableau associatif de clé un mois -aaaamm- et de valeurs l'année et le mois correspondant
  */
  public function getLesMoisDisponibles($idVisiteur){
    $req = "select fichefrais.mois as mois
    from  fichefrais
    where fichefrais.idvisiteur ='$idVisiteur'
    order by fichefrais.mois desc ";
    $rs = $this->db->query($req);
    $lesMois =array();
    $laLigne = $rs->first_row('array');
    while($laLigne != null)	{
      $mois = $laLigne['mois'];
      $numAnnee = substr( $mois,0,4);
      $numMois = substr( $mois,4,2);
      $lesMois["$mois"] = array(
        "mois"=>"$mois",
        "numAnnee"  => "$numAnnee",
        "numMois"  => "$numMois"
      );
      $laLigne = $rs->next_row('array');
    }
    return $lesMois;
  }

  /**
  * Retourne les informations d'une fiche de frais d'un visiteur pour un mois donné
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  * @return un tableau avec des champs de jointure entre une fiche de frais et la ligne d'état
  */
  public function getLesInfosFicheFrais($idVisiteur,$mois){
    $req = "select ficheFrais.idEtat as idEtat, ficheFrais.dateModif as dateModif,
    ficheFrais.nbJustificatifs as nbJustificatifs, ficheFrais.montantValide as montantValide, etat.libelle as libEtat
    from  fichefrais inner join Etat on ficheFrais.idEtat = etat.id
    where fichefrais.idvisiteur ='$idVisiteur' and fichefrais.mois = '$mois'";
    $rs = $this->db->query($req);
    $laLigne = $rs->first_row('array');
    return $laLigne;
  }

  /**
  * Modifie l'état et la date de modification d'une fiche de frais
  *
  * @param $idVisiteur
  * @param $mois sous la forme aaaamm
  * @param $etat : le nouvel état de la fiche
  */
  public function majEtatFicheFrais($idVisiteur,$mois,$etat){
    $req = "update ficheFrais
    set idEtat = '$etat', dateModif = now()
    where fichefrais.idvisiteur ='$idVisiteur' and fichefrais.mois = '$mois'";
    $this->db->simple_query($req);
  }

  /**
  * Obtient toutes les fiches (sans détail) d'un visiteur donné
  *
  * @param $idVisiteur
  */
  public function getFiches ($idVisiteur) {
    $req = "select idVisiteur, mois, montantValide, dateModif, id, libelle
    from  fichefrais inner join etat on ficheFrais.idEtat = etat.id
    where fichefrais.idvisiteur = '$idVisiteur'
    order by mois desc";
    $rs = $this->db->query($req);
    $lesFiches = $rs->result_array();
    return $lesFiches;
  }

  public function getAllFiches ($idVisiteur) {
    $req = "select idVisiteur, mois, montantValide, dateModif, libelle, id
    from fichefrais inner join etat on ficheFrais.idEtat = etat.id
    where fichefrais.idVisiteur != '$idVisiteur'
    order by idVisiteur, mois desc
    ";
    $rs = $this->db->query($req);
    $allFiches = $rs->result_array();
    return $allFiches;
  }

  /**
  * Calcule le montant total de la fiche pour un visiteur et un mois donnés
  *
  * @param $idVisiteur
  * @param $mois
  * @return le montant total de la fiche
  */
  public function totalFiche ($idVisiteur, $mois) {
    // obtention du total hors forfait
    $req = "select SUM(montant) as totalHF
    from  lignefraishorsforfait
    where idvisiteur = '$idVisiteur'
    and mois = '$mois'";
    $rs = $this->db->query($req);
    $laLigne = $rs->first_row('array');
    $totalHF = $laLigne['totalHF'];

    // obtention du total forfaitisé
    $req = "select SUM(montantApplique * quantite) as totalF
    from  lignefraisforfait
    where idvisiteur = '$idVisiteur'
    and mois = '$mois'";
    $rs = $this->db->query($req);
    $laLigne = $rs->first_row('array');
    $totalF = $laLigne['totalF'];

    return $totalHF + $totalF;
  }

  /**
  * Modifie le montantValide et la date de modification d'une fiche de frais
  *
  * @param $idVisiteur : l'id du visiteur
  * @param $mois : mois sous la forme aaaamm
  */
  public function recalculeMontantFiche($idVisiteur,$mois){

    $totalFiche = $this->totalFiche($idVisiteur,$mois);
    $req = "update ficheFrais
    set montantValide = '$totalFiche', dateModif = now()
    where fichefrais.idvisiteur ='$idVisiteur' and fichefrais.mois = '$mois'";
    $this->db->simple_query($req);
  }

  public function getInfoVisiteur($idVisiteur){
  $req ="Select nom, prenom from visiteur where id='$idVisiteur'";
  $res = $this->db->query($req);
  $ligne = $res->first_row('array');
  return $ligne;
  }

  public function setMajMontantFrais($idVisiteur, $mois, $lesFrais){

    foreach ($lesFrais as $key => $value) {
      $data = array('montantApplique' => $value);
      $where = array('idVisiteur' => $idVisiteur, 'mois' => $mois, 'idFraisForfait' => $key);
      $this->db->update('lignefraisforfait', $data, $where);
    }
    // $where = array('idVisiteur' => $idVisiteur, 'mois' => $mois) ;

    // $this->db->update('lignefraisforfait', $data, $where);

  }
  public function getMontantFrais($idVisiteur, $Mois){
    $req="select idFraisForfait, montantApplique From lignefraisforfait where idVisiteur='$idVisiteur' AND mois ='$Mois'";
    $res = $this->db->query($req);
    $Ligne = $res->result_array();
    return $Ligne;
  }

  public function DecompositionMois($uneDateFiche){
    if (strlen($uneDateFiche) == 6) {
      $Chaine = substr($uneDateFiche,4,2);
      return $Chaine;
    }else {
      return null;
    }
  }

  public function DecompositionAnnee($uneDateFiche){
    if (strlen($uneDateFiche) == 6) {
      $Chaine = substr($uneDateFiche,0,4);
      return intval($Chaine);
    }else {
      return null;
    }
  }
}
?>

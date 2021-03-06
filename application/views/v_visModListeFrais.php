<?php
$this->load->helper('url');
?>
<div id="contenu">
	<h2>Renseigner ma fiche de frais du mois <?php echo $numMois."-".$numAnnee; ?></h2>
	<?php if(!empty($notify)) echo '<p id="notify" >'.$notify.'</p>';?>

	<form method="post"  action="<?php echo base_url("c_visiteur/majForfait");?>">
		<div class="corpsForm">

			<fieldset>
				<legend>Eléments forfaitisés</legend>
				<table style="text-align: left; width : 90%; ">
					<script type="text/javascript">
					var TableaudesMontants = new Array();
					</script>

					<?php
					$i = 0;
					foreach ($lesFraisForfait as $unFrais)
					{
						?><tr>
							<td><?php
							$idFrais = $unFrais['idfrais'];
							$libelle = $unFrais['libelle'];
							$quantite = $unFrais['quantite'];

								echo "<script type='text/javascript'>
								TableaudesMontants['$idFrais']='$unFrais[montantFrais]';
								</script>";

							echo
							'<p>
							<label for="'.$idFrais.'">'.$libelle.'</label>
							<input type="text" onkeyup="ChercheAlpha(this);calculForfait();CalculFraisParFrais(this,TableaudesMontants);TotalFraisParFrais();" onkeypress="ChercheAlpha(this);calculForfait();" class="SearchAlpha" id="'.$idFrais.'" name="lesFrais['.$idFrais.']" size="10" maxlength="5" value="'.$quantite.'" />
							</p>
							';
							?></td><td><?php

							echo '
							<p>
							<label>Résultat </label>
							<input onfocus="blur();" type="text" class="ApplicationCalcul" id="Resul'.$idFrais.'" name="Unresul['.$idFrais.']" size="10" maxlength="5">
							';
							$i++;
							?></td>
						</tr><?php

					}
					?><tr>
						<td><?php
						echo
						'<p>
						<label>Total </label>
						<input disabled type="text" name="Total" id="Total" size="10" maxlength="5" value="" />
						</p>
						';
						?>
					</td>
					<td><?php
					echo
					'<p>
					<label>Total Resultats</label>
					<input disabled type="text" name="TotalFrais" id="TotalFrais" size="10" maxlength="5"/>
					</p>
					';
					?></td>
				</tr>

			</table>
		</fieldset>

	</div>
	<div class="piedForm">
		<p>
			<input id="ok" type="submit" value="Enregistrer" size="20" />
			<input id="annuler" type="reset" value="Effacer" size="20" />
		</p>
	</div>
</form>


<table class="listeLegere">
	<caption>Descriptif des éléments hors forfait</caption>
	<tr>
		<th >Date</th>
		<th >Libellé</th>
		<th >Montant</th>
		<th >&nbsp;</th>
	</tr>

	<?php
	$total=0;
	foreach( $lesFraisHorsForfait as $unFraisHorsForfait)
	{
		$libelle = $unFraisHorsForfait['libelle'];
		$date = $unFraisHorsForfait['date'];
		$montant=$unFraisHorsForfait['montant'];
		$id = $unFraisHorsForfait['id'];
		echo
		'<tr>
		<td class="date">'.$date.'</td>
		<td class="libelle">'.$libelle.'</td>
		<td class="montant">'.$montant.' €</td>
		<td class="action">'.
		anchor(	"c_visiteur/supprFrais/$id",
		"Supprimer ce frais",
		'title="Suppression d\'une ligne de frais" onclick="return confirm(\'Voulez-vous vraiment supprimer ce frais ?\');"'
		).
		'</td>
		</tr>';
		$total+=$montant;
	}
	?>
	<tr>
		<td class="date"></td>
		<td class="libelle"><b>Montant Total</b></td>
		<td class="montant"><b><?= number_format($total,2)." €" ?></b></td>
		<td class="action"><p> </p> </td>
	</tr>
</table>

<?php if (isset($erreur2))	echo '<div class ="erreur"><ul><li>'.$erreur2.'</li></ul></div>'; ?>

<form method="post" action="<?php echo base_url("c_visiteur/ajouteFrais");?>">
	<div class="corpsForm">
		<fieldset>
			<legend>Nouvel élément hors forfait</legend>
			<p>
				<label for="txtDateHF">Date (jj/mm/aaaa): </label>
				<input type="date" id="txtDateHF" required name="dateFrais" size="10" maxlength="10" value=""  />
			</p>
			<p>
				<label for="txtLibelleHF">Libellé</label>
				<input type="text" id="txtLibelleHF" required name="libelle" size="60" maxlength="256" value="" />
			</p>
			<p>
				<label for="txtMontantHF">Montant : </label>
				<input type="number" id="txtMontantHF" required name="montant" size="10" maxlength="10" value="" />
			</p>
	</fieldset>
</div>
<div class="piedForm">
	<p>
		<input id="ajouter" type="submit" value="Ajouter" size="20" />
		<input id="effacer" type="reset" value="Effacer" size="20" />
	</p>
</div>
</form>
</div>

(: ================================================================
   FICHIER : updates.xq
   PROJET  : Club Info_Tech — Mises à Jour XQuery Update Facility
   Partie 3.4 — Supporte par BaseX
   ================================================================ :)

(: U1 — INSERTION : Ajout d'un nouveau membre (1.5 pt)
   Ajoute M010 (Brahimi Amina) dans la categorie C2 (Dev. Web)
   Format ID respecte : M + 3 chiffres, non conflictuel :)
insert node
  <membre id="M010" categorieRef="C2">
    <nom>Brahimi</nom>
    <prenom>Amina</prenom>
    <email>a.brahimi@club.dz</email>
  </membre>
into doc("club.xml")//membres
,

(: U2 — MODIFICATION : Changement du coefficient de CO2 (1.5 pt)
   Etat AVANT  : coefficient="1.2"
   Etat APRES  : coefficient="2.0" :)
replace value of node doc("club.xml")//concours[@id="CO2"]/@coefficient
with "2.0"
,

(: U3 — SUPPRESSION : Retrait du participant M003 du concours CO1 (1 pt)
   Le concours CO1 subsiste avec ses autres participants M001 et M002 :)
delete node doc("club.xml")//concours[@id="CO1"]//participant[@membreRef="M003"]
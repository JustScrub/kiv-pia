#order cannot be changed due to relations and foreign keys
#first must be deleted the tables that don't have any dependents (tables with a reference to this table)

DROP TABLE IF EXISTS `recenzenti`; #dependents: none
DROP TABLE IF EXISTS `clanek` ; #dependents: recenzenti
DROP TABLE IF EXISTS `uzivatel` ; #dependents: clanek, recenzenti
DROP TABLE IF EXISTS `pravo` ;  #dependents: uzivatel
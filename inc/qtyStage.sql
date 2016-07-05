CREATE TABLE IF NOT EXISTS staged_qtys(
'partID' int(9),
'companyid' int(9),
'qty' int(9),
'vqty' int(9)
);

CREATE TRIGGER 'set_vqty' BEFORE INSERT ON 'staged_qtys'
FOR EACH ROW BEGIN
SET @qty = NEW.`qty`;
SET @vqty = (SELECT mod(truncate(rand()*10000,0),@qty)+1);

SET NEW.vqty = @vqty;

END
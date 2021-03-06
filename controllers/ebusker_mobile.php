<?

class Ebusker_mobile extends OBFController
{

	public function __construct()
	{

		parent::__construct();
	
		$this->ebuskerArtistsModel = $this->load->model('EbuskerArtists');
    $this->devicesModel = $this->load->model('Devices');

	}

	public function artist_latest_transaction()
	{

		$transaction = $this->ebuskerArtistsModel->latestTransaction(1);
		return array(true,'Latest Transaction',$transaction);

	}

  public function now_playing()
  {

    $device_id = $this->data('device_id');

    if(!preg_match('/^[0-9]+$/',$device_id)) return array(false,'Invalid device id.');

    $device = $this->devicesModel('getOne',$device_id);
    if(!$device) return array(false,'Invalid device id.');

    $data = $this->devicesModel('nowPlaying',$device_id);

		if(isset($data['media']['id']))
		{
		
			$artist = $this->ebuskerArtistsModel('mediaIdToArtist',$data['media']['id']);
			// $artist = $this->ebuskerArtistsModel('mediaIdToArtist',52);
		
			if($artist)
			{
				$data['media']['ebusker_artist']=$artist;
			}

		}

    return array(true,'Now Playing',$data);

  }

	public function transaction()
	{

		$amount = $this->data('amount');
		$comments = trim($this->data('comments'));

		if(!is_numeric($amount) || $amount<=0 || $amount>2) return array(false,'Invalid transaction amount.');

		$transaction_id = microtime(true) .'-'. rand(100000,999999);

		$description = '$'.money_format('%.2n',$amount).' from J-Smith.';
		if($comments!='') $description .= "\n\n\"".$comments.'"';

		$description = nl2br(htmlspecialchars($description));

		$this->db->query('LOCK TABLES ebusker_artists_transactions WRITE;');
		$this->db->query('set @newbal = '.$amount.' + (SELECT balance FROM `ebusker_artists_transactions` where artist_id = 1 order by id desc limit 1);');
		$this->db->query('insert into ebusker_artists_transactions set artist_id = 1, transaction_id = "'.$this->db->escape($transaction_id).'", amount = "'.$this->db->escape($amount).'",
					balance = @newbal, type = "pool", description = "'.$this->db->escape($description).'";');
		$this->db->query('UNLOCK TABLES;');

		return array(true,'Success.');

	}

}

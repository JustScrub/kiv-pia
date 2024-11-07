import requests
from common import get_id, HOST

def test_noexist():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': 'no exist'})
    assert response.status_code == 401

def test_ok():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200

def test_expired():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': get_id(-1)})
    assert response.status_code == 401

def test_low_rights():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': get_id("banned")})
    assert response.status_code == 403
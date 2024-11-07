import requests
from common import HOST

def test_noexist():
    response = requests.post(f'{HOST}/api.php?service=get_auth_key', json={'login': 'noexist', 'pass': 'noexist'})
    assert response.status_code == 401

def test_ok():
    response = requests.post(f'{HOST}/api.php?service=get_auth_key', json={'login': 'test', 'pass': 'test'})
    assert response.status_code == 200
    assert 'key' in response.json()

def test_banned():
    response = requests.post(f'{HOST}/api.php?service=get_auth_key', json={'login': 'banned', 'pass': 'test'})
    assert response.status_code == 403

def test_wrong_pass():
    response = requests.post(f'{HOST}/api.php?service=get_auth_key', json={'login': 'test', 'pass': 'wrong'})
    assert response.status_code == 401
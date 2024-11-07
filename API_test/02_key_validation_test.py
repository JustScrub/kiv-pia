import requests
from common import get_id, HOST

def test_noexist():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': 'no exist'})
    assert response.status_code == 401, response.text

def test_ok():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200, response.text

def test_expired():
    from time import sleep
    key = get_id(exp=1)
    sleep(2)
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': key})
    assert response.status_code == 401, response.text

def test_low_rights():
    response = requests.put(f'{HOST}/api.php?service=ban_users', 
                            headers={'Authorization': get_id()},
                            json=["superadmin"])
    assert response.status_code == 401, response.text
    

if __name__ == "__main__":
    test_noexist()
    test_ok()
    test_expired()
    test_low_rights()
    print("All tests passed!")
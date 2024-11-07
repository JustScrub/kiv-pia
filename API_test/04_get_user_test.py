import requests
from common import get_id, is_subdict, HOST

def test_no_param():
    response = requests.get(f'{HOST}/api.php?service=get_user', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 400
    assert "Missing query parameter" in response.json()["message"]

def test_noexist():
    response = requests.get(f'{HOST}/api.php?service=get_user&login=noexist', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 404, response.text
    assert "User not found" in response.json()["message"]

def test_nonadmin_ok():
    response = requests.get(f'{HOST}/api.php?service=get_user&login=admin', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200
    assert is_subdict( {
        "email": "admin@test.com",
        "lname": "Admin",
        "fname": "Admin",
    }, response.json())

def test_admin_ok():
    response = requests.get(f'{HOST}/api.php?service=get_user&login=admin', 
                            headers={'Authorization': get_id("admin")})
    assert response.status_code == 200
    assert is_subdict( {
        "email": "admin@test.com",
        "lname": "Admin",
        "fname": "Admin",
        "rights": 2,
        "banned": False
    }, response.json()), response.text

if __name__ == "__main__":
    test_no_param()
    test_noexist()
    test_nonadmin_ok()
    test_admin_ok()
    print("All tests passed!")
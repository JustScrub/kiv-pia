import requests, pytest
from common import get_id, dict_in_list, HOST

def test_no_param():
    response = requests.get(f'{HOST}/api.php?service=get_user_articles', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 400
    assert "Missing query parameter" in response.json()["message"]

def test_noexist():
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&login=noexist', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 404, response.text
    assert "User not found" in response.json()["message"]
    assert response.json()["redirect"] == "/api.php?service=get_user"

@pytest.mark.parametrize("ar_data", [
    {"id": 1, "articles": [5], "appr": ["yes"]},
    {"id": 2, "articles": [4], "appr": ["pending"]},
    {"id": 3, "articles": [1, 2, 3], "appr": ["pending", "yes", "no"]},
    {"id": 4, "articles": [6, 7], "appr": ["pending", "no"]},
    ])
def test_ok(ar_data):
    logins = ["superadmin", "admin@test.com", "test", "banned@test.com"]
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&login={logins[ar_data["id"]-1]}', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200
    try:
        j = response.json()
    except ValueError:
        assert False, response.text
    assert isinstance(j, list)
    assert len(j) == len(ar_data["articles"])
    for i in range(len(j)):
        d = {
            "id": ar_data["articles"][i],
            "author_id": ar_data["id"],
            "title": f"Testovaci clanek {ar_data['articles'][i]}",
            "descr":  'Testovaci clanek pro testovani',
            "key-words": 'test, testovani',
            "approved": ar_data["appr"][i],
            }
        assert dict_in_list(j, d), (d,j)

if __name__ == "__main__":
    test_no_param()
    test_noexist()
    test_ok({"id": "superadmin", "articles": [5], "appr": ["yes"]})
    test_ok({"id": "admin@test.com", "articles": [4], "appr": ["pending"]})
    test_ok({"id": "test", "articles": [1, 2, 3], "appr": ["pending", "yes", "no"]})
    test_ok({"id": "banned@test.com", "articles": [6, 7], "appr": ["pending", "no"]})
    print("All tests passed!")
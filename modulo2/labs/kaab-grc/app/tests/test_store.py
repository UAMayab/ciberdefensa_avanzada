"""Persistencia: escritura atómica, claves desconocidas preservadas, versión futura rechazada."""

import json

import pytest

from kaab import store


def test_archivo_inexistente_devuelve_estructura_base(tmp_path):
    assert store.load(tmp_path / "no.json") == {"schema_version": store.SCHEMA_VERSION}


def test_ida_y_vuelta(tmp_path):
    ruta = tmp_path / "ws.json"
    datos = {"schema_version": 1, "marco": {"tolerancia": "Moderado y con dueño"}}
    store.save(datos, ruta)
    assert store.load(ruta) == datos


def test_preserva_claves_desconocidas(tmp_path):
    ruta = tmp_path / "ws.json"
    ruta.write_text(json.dumps({"schema_version": 1, "clave_de_otra_version": {"x": 1}}), encoding="utf-8")
    datos = store.load(ruta)
    datos["marco"] = {"tolerancia": "x"}
    store.save(datos, ruta)
    assert store.load(ruta)["clave_de_otra_version"] == {"x": 1}


def test_rechaza_version_futura(tmp_path):
    ruta = tmp_path / "ws.json"
    ruta.write_text(json.dumps({"schema_version": 99}), encoding="utf-8")
    with pytest.raises(ValueError, match="versión más nueva"):
        store.load(ruta)


def test_no_deja_archivos_temporales(tmp_path):
    ruta = tmp_path / "ws.json"
    store.save({"schema_version": 1}, ruta)
    assert sorted(p.name for p in tmp_path.iterdir()) == ["ws.json"]

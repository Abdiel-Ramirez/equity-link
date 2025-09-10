import { useState } from "react";
import Swal from "sweetalert2";
import { apiFetch } from "../../utils/utils";

export default function InvoiceUpload() {
  const [file, setFile] = useState(null);

  const handleFileChange = (e) => {
    setFile(e.target.files[0]);
  };

  const handleSubmit = async () => {
    if (!file) {
      Swal.fire("Error", "Selecciona un archivo antes de subir", "error");
      return;
    }

    const formData = new FormData();
    formData.append("xml", file);

    try {
      await apiFetch("http://localhost:8000/api/invoices", {
        method: "POST",
        body: formData,
      });
      Swal.fire("Éxito", "Factura cargada correctamente", "success");
      setFile(null);
    } catch (err) {
      Swal.fire(
        "Error",
        err.message || "No se pudo cargar la factura",
        "error"
      );
    }
  };

  return (
    <div className="invoice-card card">
      <h3>Subir Factura</h3>
      <p>
        Selecciona el archivo XML de la factura que deseas subir. Solo se
        aceptan archivos .xml.
      </p>

      <div className="invoice-upload-input">
        <input type="file" accept=".xml" onChange={handleFileChange} />
        <button onClick={handleSubmit} disabled={!file}>
          Subir Factura
        </button>
      </div>
    </div>
  );
}

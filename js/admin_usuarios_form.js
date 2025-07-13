document.addEventListener('DOMContentLoaded', function() {
    const userTypeSelect = document.getElementById('tipo'); // ID do select de tipo de usuário
    const imobiliariaSelectGroup = document.getElementById('imobiliaria_select_group'); // ID da div do campo de imobiliária

    function toggleImobiliariaSelect() {
        if (userTypeSelect && imobiliariaSelectGroup) { // Garante que os elementos existem
            const selectedType = userTypeSelect.value;
            // Se o tipo for 'corretor_imobiliaria' ou 'admin_imobiliaria', mostra a imobiliária
            if (selectedType === 'corretor_imobiliaria' || selectedType === 'admin_imobiliaria') {
                imobiliariaSelectGroup.style.display = 'block';
                imobiliariaSelectGroup.querySelector('select').setAttribute('required', 'required'); // Torna o select obrigatório
            } else {
                // Caso contrário, esconde a imobiliária e remove a obrigatoriedade
                imobiliariaSelectGroup.style.display = 'none';
                imobiliariaSelectGroup.querySelector('select').removeAttribute('required');
                imobiliariaSelectGroup.querySelector('select').value = ""; // Limpa a seleção
            }
        }
    }

    // Chama a função ao carregar a página para definir o estado inicial do campo de imobiliária
    toggleImobiliariaSelect();

    // Adiciona um event listener para reagir a mudanças no select de tipo de usuário
    if (userTypeSelect) { // Garante que o select existe antes de adicionar o listener
        userTypeSelect.addEventListener('change', toggleImobiliariaSelect);
    }
});
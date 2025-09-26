import { useState } from "react";
import { AppHeader } from "@/components/AppHeader";
import { AppFooter } from "@/components/AppFooter";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { User, Mail, Phone, MapPin, Edit2, Save, X, CreditCard, Lock, Home } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function Profile() {
  const { toast } = useToast();
  const [isEditing, setIsEditing] = useState(false);
  const [userData, setUserData] = useState({
    // Informações Pessoais
    name: "João Silva",
    cpf: "123.456.789-00",
    phone: "(11) 9999-9999",
    cellphone: "(11) 99999-9999",
    alternativeEmail: "joao.alt@email.com",
    
    // Endereço
    cep: "01234-567",
    street: "Rua das Flores",
    number: "123",
    complement: "Apto 45",
    neighborhood: "Centro",
    city: "São Paulo",
    state: "SP",
    
    // Senha
    currentPassword: "",
    newPassword: "",
    confirmPassword: ""
  });

  const [editData, setEditData] = useState(userData);

  const handleEdit = () => {
    setIsEditing(true);
    setEditData(userData);
  };

  const handleSave = () => {
    setUserData(editData);
    setIsEditing(false);
    toast({
      title: "Perfil atualizado",
      description: "Seus dados foram salvos com sucesso!",
    });
  };

  const handleCancel = () => {
    setIsEditing(false);
    setEditData(userData);
  };

  return (
    <div className="min-h-screen bg-background">
      <AppHeader showBackButton title="Meu Perfil" />
      
      <div className="max-w-md mx-auto -mt-4 space-y-6 pb-8">
        {/* Profile Avatar */}
        <Card className="bg-gradient-card shadow-card border border-border/50 mx-4 p-6">
          <div className="flex flex-col items-center space-y-4">
            <div className="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center">
              <User className="h-10 w-10 text-primary" />
            </div>
            <div className="text-center">
              <h2 className="text-xl font-semibold text-card-foreground">
                {userData.name}
              </h2>
              <p className="text-muted-foreground">Membro Klube Cash</p>
            </div>
          </div>
        </Card>

        {/* Informações Pessoais */}
        <Card className="bg-gradient-card shadow-card border border-border/50 mx-4 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-card-foreground flex items-center">
              <User className="h-5 w-5 mr-2 text-primary" />
              Informações Pessoais
            </h3>
            {!isEditing ? (
              <Button
                variant="ghost"
                size="sm"
                onClick={handleEdit}
                className="text-primary hover:bg-primary/10"
              >
                <Edit2 className="h-4 w-4 mr-2" />
                Editar
              </Button>
            ) : (
              <div className="flex space-x-2">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={handleCancel}
                  className="text-destructive hover:bg-destructive/10"
                >
                  <X className="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={handleSave}
                  className="text-success hover:bg-success/10"
                >
                  <Save className="h-4 w-4" />
                </Button>
              </div>
            )}
          </div>

          <div className="space-y-4">
            {/* Nome Completo */}
            <div className="space-y-2">
              <Label htmlFor="name" className="text-sm font-medium text-card-foreground">
                Nome Completo *
              </Label>
              {isEditing ? (
                <Input
                  id="name"
                  value={editData.name}
                  onChange={(e) => setEditData({...editData, name: e.target.value})}
                  className="border-border/50 focus:border-primary"
                />
              ) : (
                <p className="text-card-foreground bg-muted p-3 rounded-md">
                  {userData.name}
                </p>
              )}
            </div>

            {/* CPF */}
            <div className="space-y-2">
              <Label htmlFor="cpf" className="text-sm font-medium text-card-foreground">
                CPF
              </Label>
              {isEditing ? (
                <Input
                  id="cpf"
                  value={editData.cpf}
                  onChange={(e) => setEditData({...editData, cpf: e.target.value})}
                  className="border-border/50 focus:border-primary"
                  placeholder="000.000.000-00"
                />
              ) : (
                <div className="space-y-1">
                  <p className="text-card-foreground bg-muted p-3 rounded-md">
                    {userData.cpf}
                  </p>
                  <p className="text-xs text-muted-foreground flex items-center">
                    <CreditCard className="h-3 w-3 mr-1" />
                    CPF já validado e não pode ser alterado
                  </p>
                </div>
              )}
            </div>

            {/* Telefone e Celular */}
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="phone" className="text-sm font-medium text-card-foreground">
                  Telefone
                </Label>
                {isEditing ? (
                  <Input
                    id="phone"
                    value={editData.phone}
                    onChange={(e) => setEditData({...editData, phone: e.target.value})}
                    className="border-border/50 focus:border-primary"
                    placeholder="(00) 0000-0000"
                  />
                ) : (
                  <p className="text-card-foreground bg-muted p-3 rounded-md">
                    {userData.phone}
                  </p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="cellphone" className="text-sm font-medium text-card-foreground">
                  Celular
                </Label>
                {isEditing ? (
                  <Input
                    id="cellphone"
                    value={editData.cellphone}
                    onChange={(e) => setEditData({...editData, cellphone: e.target.value})}
                    className="border-border/50 focus:border-primary"
                    placeholder="(00) 00000-0000"
                  />
                ) : (
                  <p className="text-card-foreground bg-muted p-3 rounded-md">
                    {userData.cellphone}
                  </p>
                )}
              </div>
            </div>

            {/* E-mail Alternativo */}
            <div className="space-y-2">
              <Label htmlFor="alternativeEmail" className="text-sm font-medium text-card-foreground">
                E-mail Alternativo
              </Label>
              {isEditing ? (
                <Input
                  id="alternativeEmail"
                  type="email"
                  value={editData.alternativeEmail}
                  onChange={(e) => setEditData({...editData, alternativeEmail: e.target.value})}
                  className="border-border/50 focus:border-primary"
                  placeholder="email.alternativo@exemplo.com"
                />
              ) : (
                <p className="text-card-foreground bg-muted p-3 rounded-md">
                  {userData.alternativeEmail}
                </p>
              )}
            </div>

            {isEditing && (
              <Button className="w-full bg-primary hover:bg-primary/90 text-primary-foreground">
                <Save className="h-4 w-4 mr-2" />
                Salvar Alterações
              </Button>
            )}
          </div>
        </Card>

        {/* Endereço */}
        <Card className="bg-gradient-card shadow-card border border-border/50 mx-4 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-card-foreground flex items-center">
              <Home className="h-5 w-5 mr-2 text-primary" />
              Endereço
            </h3>
          </div>

          <div className="space-y-4">
            {/* CEP e Logradouro */}
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="cep" className="text-sm font-medium text-card-foreground">
                  CEP
                </Label>
                <Input
                  id="cep"
                  value={userData.cep}
                  className="border-border/50 focus:border-primary"
                  placeholder="00000-000"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="street" className="text-sm font-medium text-card-foreground">
                  Logradouro
                </Label>
                <Input
                  id="street"
                  value={userData.street}
                  className="border-border/50 focus:border-primary"
                />
              </div>
            </div>

            {/* Número e Complemento */}
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="number" className="text-sm font-medium text-card-foreground">
                  Número
                </Label>
                <Input
                  id="number"
                  value={userData.number}
                  className="border-border/50 focus:border-primary"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="complement" className="text-sm font-medium text-card-foreground">
                  Complemento
                </Label>
                <Input
                  id="complement"
                  value={userData.complement}
                  className="border-border/50 focus:border-primary"
                />
              </div>
            </div>

            {/* Bairro */}
            <div className="space-y-2">
              <Label htmlFor="neighborhood" className="text-sm font-medium text-card-foreground">
                Bairro
              </Label>
              <Input
                id="neighborhood"
                value={userData.neighborhood}
                className="border-border/50 focus:border-primary"
              />
            </div>

            {/* Cidade e Estado */}
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="city" className="text-sm font-medium text-card-foreground">
                  Cidade
                </Label>
                <Input
                  id="city"
                  value={userData.city}
                  className="border-border/50 focus:border-primary"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="state" className="text-sm font-medium text-card-foreground">
                  Estado
                </Label>
                <Input
                  id="state"
                  value={userData.state}
                  className="border-border/50 focus:border-primary"
                />
              </div>
            </div>

            <Button className="w-full bg-primary hover:bg-primary/90 text-primary-foreground">
              <Save className="h-4 w-4 mr-2" />
              Salvar Endereço
            </Button>
          </div>
        </Card>

        {/* Alterar Senha */}
        <Card className="bg-gradient-card shadow-card border border-border/50 mx-4 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-card-foreground flex items-center">
              <Lock className="h-5 w-5 mr-2 text-primary" />
              Alterar Senha
            </h3>
          </div>

          <div className="space-y-4">
            {/* Senha Atual */}
            <div className="space-y-2">
              <Label htmlFor="currentPassword" className="text-sm font-medium text-card-foreground">
                Senha Atual *
              </Label>
              <Input
                id="currentPassword"
                type="password"
                value={userData.currentPassword}
                className="border-border/50 focus:border-primary"
                placeholder="Digite sua senha atual"
              />
            </div>

            {/* Nova Senha */}
            <div className="space-y-2">
              <Label htmlFor="newPassword" className="text-sm font-medium text-card-foreground">
                Nova Senha *
              </Label>
              <Input
                id="newPassword"
                type="password"
                value={userData.newPassword}
                className="border-border/50 focus:border-primary"
                placeholder="Digite sua nova senha"
              />
              <p className="text-xs text-muted-foreground">
                A senha deve ter pelo menos 8 caracteres
              </p>
            </div>

            {/* Confirmar Nova Senha */}
            <div className="space-y-2">
              <Label htmlFor="confirmPassword" className="text-sm font-medium text-card-foreground">
                Confirmar Nova Senha *
              </Label>
              <Input
                id="confirmPassword"
                type="password"
                value={userData.confirmPassword}
                className="border-border/50 focus:border-primary"
                placeholder="Confirme sua nova senha"
              />
            </div>

            <Button className="w-full bg-primary hover:bg-primary/90 text-primary-foreground">
              <Lock className="h-4 w-4 mr-2" />
              Alterar Senha
            </Button>
          </div>
        </Card>
      </div>

      <AppFooter />
    </div>
  );
}